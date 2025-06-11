<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Tournament\Models\Team;
use App\Domain\Team\Repositories\TeamRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class EloquentTeamRepository implements TeamRepositoryInterface
{
    /**
     * Get all teams with optional filters
     */
    public function getAll(array $filters = [], array $with = []): Collection
    {
        return $this->applyFilters(Team::query(), $filters)
            ->with($with)
            ->get();
    }

    /**
     * Get paginated teams
     */
    public function getPaginated(int $perPage = 15, array $filters = [], array $with = []): LengthAwarePaginator
    {
        return $this->applyFilters(Team::query(), $filters)
            ->with($with)
            ->paginate($perPage);
    }

    /**
     * Find team by ID
     */
    public function findById(int $id, array $with = []): ?Team
    {
        return Team::with($with)->find($id);
    }

    /**
     * Find team by players
     */
    public function findByPlayers(int $player1Id, int $player2Id, array $with = []): ?Team
    {
        return Team::with($with)
            ->where(function ($query) use ($player1Id, $player2Id) {
                $query->where(function ($q) use ($player1Id, $player2Id) {
                    $q->where('player1_id', $player1Id)
                      ->where('player2_id', $player2Id);
                })->orWhere(function ($q) use ($player1Id, $player2Id) {
                    $q->where('player1_id', $player2Id)
                      ->where('player2_id', $player1Id);
                });
            })
            ->first();
    }

    /**
     * Create a new team
     */
    public function create(array $data): Team
    {
        // Calculate team rating from player ratings (only if not explicitly provided)
        if (!isset($data['team_rating']) || $data['team_rating'] === null) {
            $data['team_rating'] = $this->calculateInitialTeamRating($data);
        }

        // Generate team name if not provided
        if (!isset($data['name']) || empty($data['name'])) {
            $data['name'] = $this->generateTeamName($data['player1_id'], $data['player2_id']);
        }

        // Set default status if not provided
        if (!isset($data['status'])) {
            $data['status'] = Team::STATUS_ACTIVE;
        }

        // Set partnership start date if not provided
        if (!isset($data['partnership_start_date'])) {
            $data['partnership_start_date'] = now();
        }

        // Set captain if not provided (default to player1)
        if (!isset($data['captain_id'])) {
            $data['captain_id'] = $data['player1_id'];
        }

        return Team::create($data);
    }

    /**
     * Update team
     */
    public function update(Team $team, array $data): Team
    {
        // Recalculate team rating if players changed
        if (isset($data['player1_id']) || isset($data['player2_id'])) {
            $data['team_rating'] = $this->calculateInitialTeamRating(array_merge($team->toArray(), $data));
        }

        $team->update($data);
        return $team->fresh();
    }

    /**
     * Delete team
     */
    public function delete(Team $team): bool
    {
        return $team->delete();
    }

    /**
     * Get teams by player
     */
    public function getByPlayer(int $playerId, array $with = []): Collection
    {
        return Team::with($with)
            ->where(function ($query) use ($playerId) {
                $query->where('player1_id', $playerId)
                      ->orWhere('player2_id', $playerId);
            })
            ->get();
    }

    /**
     * Get active teams by player
     */
    public function getActiveByPlayer(int $playerId, array $with = []): Collection
    {
        return Team::with($with)
            ->where('status', Team::STATUS_ACTIVE)
            ->where(function ($query) use ($playerId) {
                $query->where('player1_id', $playerId)
                      ->orWhere('player2_id', $playerId);
            })
            ->get();
    }

    /**
     * Get teams by captain
     */
    public function getByCaptain(int $captainId, array $with = []): Collection
    {
        return Team::with($with)
            ->where('captain_id', $captainId)
            ->get();
    }

    /**
     * Get teams by status
     */
    public function getByStatus(string $status, array $with = []): Collection
    {
        return Team::with($with)
            ->where('status', $status)
            ->get();
    }

    /**
     * Get teams by tournament
     */
    public function getByTournament(int $tournamentId, array $with = []): Collection
    {
        return Team::with($with)
            ->whereHas('tournamentParticipants', function ($query) use ($tournamentId) {
                $query->where('tournament_id', $tournamentId);
            })
            ->get();
    }

    /**
     * Get top rated teams
     */
    public function getTopRated(int $limit = 10, array $with = []): Collection
    {
        return Team::with($with)
            ->where('status', Team::STATUS_ACTIVE)
            ->orderBy('team_rating', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get recent teams
     */
    public function getRecent(int $limit = 10, array $with = []): Collection
    {
        return Team::with($with)
            ->latest('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Search teams by name
     */
    public function search(string $query, array $filters = []): Collection
    {
        $builder = Team::query()
            ->where('name', 'LIKE', "%{$query}%");

        return $this->applyFilters($builder, $filters)->get();
    }

    /**
     * Get teams available for tournament
     */
    public function getAvailableForTournament(int $tournamentId): Collection
    {
        return Team::where('status', Team::STATUS_ACTIVE)
            ->whereDoesntHave('tournamentParticipants', function ($query) use ($tournamentId) {
                $query->where('tournament_id', $tournamentId)
                      ->whereIn('status', ['confirmed', 'pending']);
            })
            ->get();
    }

    /**
     * Get team statistics
     */
    public function getStatistics(): array
    {
        $teams = Team::with(['player1', 'player2'])->get();

        return [
            'total_teams' => $teams->count(),
            'active_teams' => $teams->where('status', Team::STATUS_ACTIVE)->count(),
            'inactive_teams' => $teams->where('status', Team::STATUS_INACTIVE)->count(),
            'disbanded_teams' => $teams->where('status', Team::STATUS_DISBANDED)->count(),
            'average_team_rating' => round($teams->avg('team_rating'), 2),
            'average_partnership_duration' => $this->calculateAveragePartnershipDuration($teams),
            'gender_combinations' => $this->getGenderCombinations($teams),
            'most_successful_teams' => $this->getMostSuccessfulTeams(5),
            'rating_distribution' => $this->getRatingDistribution($teams),
            'tournament_participation' => [
                'teams_with_tournaments' => $teams->where('tournaments_played', '>', 0)->count(),
                'average_tournaments_per_team' => round($teams->avg('tournaments_played'), 2),
                'total_tournament_participations' => $teams->sum('tournaments_played'),
            ],
        ];
    }

    /**
     * Count teams by status
     */
    public function countByStatus(): array
    {
        return Team::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
    }

    /**
     * Update team rating
     */
    public function updateTeamRating(Team $team, int $newRating, array $matchData = []): Team
    {
        $oldRating = $team->team_rating;
        
        // Update rating history (stored in partnership_notes as JSON for now)
        $ratingHistory = json_decode($team->partnership_notes, true) ?? [];
        $ratingHistory[] = [
            'old_rating' => $oldRating,
            'new_rating' => $newRating,
            'change' => $newRating - $oldRating,
            'match_data' => $matchData,
            'date' => now()->toISOString(),
        ];

        $team->update([
            'team_rating' => $newRating,
            'partnership_notes' => json_encode($ratingHistory),
        ]);

        return $team->fresh();
    }

    /**
     * Update team statistics after match
     */
    public function updateMatchStatistics(Team $team, array $matchData): Team
    {
        $isWin = $matchData['is_win'] ?? false;
        
        $team->increment('total_matches');
        
        if ($isWin) {
            $team->increment('wins');
            // Note: current_win_streak and best_win_streak columns don't exist in current schema
            // Would need migration to add these fields
        }

        // Note: last_match_date column doesn't exist in current schema
        // Would need migration to add this field
        $team->save();

        return $team->fresh();
    }

    /**
     * Update team tournament statistics
     */
    public function updateTournamentStatistics(Team $team, array $tournamentData): Team
    {
        $team->increment('tournaments_played');
        
        if ($tournamentData['won'] ?? false) {
            $team->increment('tournaments_won');
        }

        if (isset($tournamentData['final_position']) && $tournamentData['final_position'] <= 3) {
            // Update best finish
            if (is_null($team->best_finish) || $tournamentData['final_position'] < $team->best_finish) {
                $team->best_finish = $tournamentData['final_position'];
            }
        }

        $team->save();
        return $team->fresh();
    }

    /**
     * Calculate initial team rating from player ratings
     */
    private function calculateInitialTeamRating(array $data): int
    {
        if (!isset($data['player1_id']) || !isset($data['player2_id'])) {
            return 1000; // Default rating
        }

        $player1 = \App\Domain\Player\Models\Player::find($data['player1_id']);
        $player2 = \App\Domain\Player\Models\Player::find($data['player2_id']);

        if (!$player1 || !$player2) {
            return 1000; // Default rating
        }

        // Team rating is average of both players + bonus for compatibility
        $averageRating = ($player1->skill_rating + $player2->skill_rating) / 2;
        
        // Add compatibility bonus (up to 100 points)
        $compatibilityBonus = $this->calculateCompatibilityBonus($player1, $player2);
        
        return (int) round($averageRating + $compatibilityBonus);
    }

    /**
     * Calculate compatibility bonus between players
     */
    private function calculateCompatibilityBonus($player1, $player2): int
    {
        $bonus = 0;

        // Skill level similarity bonus
        $ratingDifference = abs($player1->skill_rating - $player2->skill_rating);
        if ($ratingDifference <= 100) {
            $bonus += 50; // Very similar skill levels
        } elseif ($ratingDifference <= 200) {
            $bonus += 25; // Somewhat similar skill levels
        }

        // Playing style compatibility (if available)
        if ($player1->playing_style && $player2->playing_style) {
            $compatibleStyles = [
                'aggressive' => ['defensive', 'all-court'],
                'defensive' => ['aggressive', 'all-court'],
                'all-court' => ['aggressive', 'defensive', 'all-court'],
                'serve-and-volley' => ['baseline', 'all-court'],
                'baseline' => ['serve-and-volley', 'all-court'],
            ];

            if (isset($compatibleStyles[$player1->playing_style]) && 
                in_array($player2->playing_style, $compatibleStyles[$player1->playing_style])) {
                $bonus += 25;
            }
        }

        // Experience bonus
        $totalTournaments = $player1->total_tournaments + $player2->total_tournaments;
        if ($totalTournaments >= 20) {
            $bonus += 25; // Experienced players
        } elseif ($totalTournaments >= 10) {
            $bonus += 15; // Moderately experienced
        }

        return min($bonus, 100); // Cap at 100 points
    }

    /**
     * Generate team name from player names
     */
    private function generateTeamName(int $player1Id, int $player2Id): string
    {
        $player1 = \App\Domain\Player\Models\Player::find($player1Id);
        $player2 = \App\Domain\Player\Models\Player::find($player2Id);

        if (!$player1 || !$player2) {
            return "Team #{$player1Id}#{$player2Id}";
        }

        $name1 = explode(' ', $player1->player_name)[0]; // First name
        $name2 = explode(' ', $player2->player_name)[0]; // First name

        return "{$name1} & {$name2}";
    }

    /**
     * Calculate average partnership duration
     */
    private function calculateAveragePartnershipDuration(Collection $teams): int
    {
        $activeDurations = $teams
            ->where('status', Team::STATUS_ACTIVE)
            ->whereNotNull('partnership_start_date')
            ->map(function ($team) {
                return now()->diffInDays($team->partnership_start_date);
            });

        return $activeDurations->avg() ?? 0;
    }

    /**
     * Get gender combinations statistics
     */
    private function getGenderCombinations(Collection $teams): array
    {
        $combinations = [];

        foreach ($teams as $team) {
            if ($team->player1 && $team->player2) {
                $gender1 = $team->player1->gender;
                $gender2 = $team->player2->gender;
                
                $key = $gender1 === $gender2 ? "{$gender1}-{$gender2}" : 'mixed';
                $combinations[$key] = ($combinations[$key] ?? 0) + 1;
            }
        }

        return $combinations;
    }

    /**
     * Get most successful teams
     */
    private function getMostSuccessfulTeams(int $limit): array
    {
        return Team::with(['player1', 'player2'])
            ->where('tournaments_won', '>', 0)
            ->orderBy('tournaments_won', 'desc')
            ->orderBy('team_rating', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($team) {
                return [
                    'id' => $team->id,
                    'name' => $team->name,
                    'tournaments_won' => $team->tournaments_won,
                    'total_tournaments' => $team->total_tournaments,
                    'win_rate' => $team->total_tournaments > 0 
                        ? round(($team->tournaments_won / $team->total_tournaments) * 100, 1) 
                        : 0,
                    'team_rating' => $team->team_rating,
                ];
            })
            ->toArray();
    }

    /**
     * Get rating distribution
     */
    private function getRatingDistribution(Collection $teams): array
    {
        return [
            'under_1000' => $teams->where('team_rating', '<', 1000)->count(),
            '1000_1499' => $teams->whereBetween('team_rating', [1000, 1499])->count(),
            '1500_1999' => $teams->whereBetween('team_rating', [1500, 1999])->count(),
            '2000_2499' => $teams->whereBetween('team_rating', [2000, 2499])->count(),
            '2500_plus' => $teams->where('team_rating', '>=', 2500)->count(),
        ];
    }

    /**
     * Get position title for achievements
     */
    private function getPositionTitle(int $position): string
    {
        return match($position) {
            1 => 'Tournament Champions',
            2 => 'Tournament Runners-up',
            3 => 'Tournament Bronze Medal',
            default => 'Top Finishers'
        };
    }

    /**
     * Get ordinal number (1st, 2nd, 3rd, etc.)
     */
    private function getOrdinal(int $number): string
    {
        $suffix = match($number % 10) {
            1 => $number % 100 === 11 ? 'th' : 'st',
            2 => $number % 100 === 12 ? 'th' : 'nd',
            3 => $number % 100 === 13 ? 'th' : 'rd',
            default => 'th'
        };

        return $number . $suffix;
    }

    /**
     * Apply filters to query builder
     */
    private function applyFilters(Builder $query, array $filters): Builder
    {
        foreach ($filters as $key => $value) {
            if ($value === null) {
                continue;
            }

            switch ($key) {
                case 'status':
                    $query->where('status', $value);
                    break;
                    
                case 'player_id':
                    $query->where(function ($q) use ($value) {
                        $q->where('player1_id', $value)
                          ->orWhere('player2_id', $value);
                    });
                    break;
                    
                case 'captain_id':
                    $query->where('captain_id', $value);
                    break;
                    
                case 'team_rating_min':
                    $query->where('team_rating', '>=', $value);
                    break;
                    
                case 'team_rating_max':
                    $query->where('team_rating', '<=', $value);
                    break;
                    
                case 'tournaments_won_min':
                    $query->where('tournaments_won', '>=', $value);
                    break;
                    
                case 'has_tournaments':
                    if ($value) {
                        $query->where('total_tournaments', '>', 0);
                    } else {
                        $query->where('total_tournaments', '=', 0);
                    }
                    break;
                    
                case 'partnership_duration_min':
                    $query->where('partnership_start_date', '<=', now()->subDays($value));
                    break;
                    
                case 'search':
                    $query->where('name', 'LIKE', "%{$value}%");
                    break;
                    
                case 'gender_combination':
                    // This would require joining with players table
                    // Implementation depends on specific requirements
                    break;
            }
        }

        return $query;
    }
} 