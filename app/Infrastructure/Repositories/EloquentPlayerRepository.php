<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Player\Models\Player;
use App\Domain\Player\Repositories\PlayerRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class EloquentPlayerRepository implements PlayerRepositoryInterface
{
    /**
     * Get all players with optional filters
     */
    public function getAll(array $filters = [], array $with = []): Collection
    {
        return $this->applyFilters(Player::query(), $filters)
            ->with($with)
            ->get();
    }

    /**
     * Get paginated players
     */
    public function getPaginated(int $perPage = 15, array $filters = [], array $with = []): LengthAwarePaginator
    {
        return $this->applyFilters(Player::query(), $filters)
            ->with($with)
            ->paginate($perPage);
    }

    /**
     * Find player by ID
     */
    public function findById(int $id, array $with = []): ?Player
    {
        return Player::with($with)->find($id);
    }

    /**
     * Find player by email
     */
    public function findByEmail(string $email, array $with = []): ?Player
    {
        return Player::with($with)->where('email', $email)->first();
    }

    /**
     * Create a new player
     */
    public function create(array $data): Player
    {
        return Player::create($data);
    }

    /**
     * Update player
     */
    public function update(Player $player, array $data): Player
    {
        $player->update($data);
        return $player->fresh();
    }

    /**
     * Delete player
     */
    public function delete(Player $player): bool
    {
        return $player->delete();
    }

    /**
     * Get players by gender
     */
    public function getByGender(string $gender, array $with = []): Collection
    {
        return Player::with($with)
            ->where('gender', $gender)
            ->get();
    }

    /**
     * Get players by skill level range
     */
    public function getBySkillRange(int $minRating, int $maxRating, array $with = []): Collection
    {
        return Player::with($with)
            ->whereBetween('skill_rating', [$minRating, $maxRating])
            ->get();
    }

    /**
     * Get players by country
     */
    public function getByCountry(string $country, array $with = []): Collection
    {
        return Player::with($with)
            ->where('country', $country)
            ->get();
    }

    /**
     * Get players by city
     */
    public function getByCity(string $city, array $with = []): Collection
    {
        return Player::with($with)
            ->where('city', $city)
            ->get();
    }

    /**
     * Search players by name or email
     */
    public function search(string $query, array $filters = []): Collection
    {
        $builder = Player::query()
            ->where(function ($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                  ->orWhere('email', 'LIKE', "%{$query}%");
            });

        return $this->applyFilters($builder, $filters)->get();
    }

    /**
     * Get verified players
     */
    public function getVerified(array $with = []): Collection
    {
        return Player::with($with)
            ->where('verification_status', 'verified')
            ->get();
    }

    /**
     * Get players with highest ratings
     */
    public function getTopRated(int $limit = 10, array $with = []): Collection
    {
        return Player::with($with)
            ->orderBy('skill_rating', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get recent players (recently joined)
     */
    public function getRecent(int $limit = 10, array $with = []): Collection
    {
        return Player::with($with)
            ->latest('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Get active players (recently active)
     */
    public function getActive(array $with = []): Collection
    {
        return Player::with($with)
            ->whereNotNull('last_active_at')
            ->where('last_active_at', '>=', now()->subDays(30))
            ->orderBy('last_active_at', 'desc')
            ->get();
    }

    /**
     * Get players available for tournaments
     */
    public function getAvailableForTournament(int $tournamentId): Collection
    {
        return Player::whereDoesntHave('tournamentParticipants', function ($query) use ($tournamentId) {
            $query->where('tournament_id', $tournamentId)
                  ->whereIn('status', ['confirmed', 'pending']);
        })->get();
    }

    /**
     * Get players by tournament participation
     */
    public function getByTournament(int $tournamentId, array $with = []): Collection
    {
        return Player::with($with)
            ->whereHas('tournamentParticipants', function ($query) use ($tournamentId) {
                $query->where('tournament_id', $tournamentId);
            })
            ->get();
    }

    /**
     * Get players looking for partners (for doubles)
     */
    public function getLookingForPartners(string $gender = null): Collection
    {
        $query = Player::where('looking_for_partner', true);

        if ($gender) {
            $query->where('gender', $gender);
        }

        return $query->get();
    }

    /**
     * Get player statistics
     */
    public function getStatistics(): array
    {
        return [
            'total_players' => Player::count(),
            'verified_players' => Player::where('verification_status', 'verified')->count(),
            'active_players' => Player::where('last_active_at', '>=', now()->subDays(30))->count(),
            'average_skill_rating' => round(Player::avg('skill_rating'), 2),
            'gender_distribution' => Player::selectRaw('gender, COUNT(*) as count')
                ->groupBy('gender')
                ->pluck('count', 'gender')
                ->toArray(),
            'country_distribution' => Player::selectRaw('country, COUNT(*) as count')
                ->whereNotNull('country')
                ->groupBy('country')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->pluck('count', 'country')
                ->toArray(),
            'skill_level_distribution' => $this->countBySkillLevel(),
            'verification_status_distribution' => $this->countByVerificationStatus(),
        ];
    }

    /**
     * Count players by verification status
     */
    public function countByVerificationStatus(): array
    {
        return Player::selectRaw('verification_status, COUNT(*) as count')
            ->groupBy('verification_status')
            ->pluck('count', 'verification_status')
            ->toArray();
    }

    /**
     * Count players by skill level
     */
    public function countBySkillLevel(): array
    {
        return [
            'beginner' => Player::where('skill_rating', '<', 1000)->count(),
            'intermediate' => Player::whereBetween('skill_rating', [1000, 1499])->count(),
            'advanced' => Player::whereBetween('skill_rating', [1500, 1999])->count(),
            'expert' => Player::whereBetween('skill_rating', [2000, 2499])->count(),
            'professional' => Player::where('skill_rating', '>=', 2500)->count(),
        ];
    }

    /**
     * Update player skill rating
     */
    public function updateSkillRating(Player $player, int $newRating, array $matchData = []): Player
    {
        $oldRating = $player->skill_rating;
        
        // Update rating history
        $ratingHistory = $player->skill_rating_history ?? [];
        $ratingHistory[] = [
            'old_rating' => $oldRating,
            'new_rating' => $newRating,
            'change' => $newRating - $oldRating,
            'match_data' => $matchData,
            'date' => now()->toISOString(),
        ];

        $player->update([
            'skill_rating' => $newRating,
            'skill_rating_history' => $ratingHistory,
            'skill_level' => $this->calculateSkillLevel($newRating),
        ]);

        return $player->fresh();
    }

    /**
     * Update player statistics after match
     */
    public function updateMatchStatistics(Player $player, array $matchData): Player
    {
        $isWin = $matchData['is_win'] ?? false;
        
        $player->increment('total_matches');
        
        if ($isWin) {
            $player->increment('matches_won');
            $player->increment('current_win_streak');
            
            // Update best win streak
            if ($player->current_win_streak > $player->best_win_streak) {
                $player->best_win_streak = $player->current_win_streak;
            }
        } else {
            $player->current_win_streak = 0;
        }

        $player->last_match_date = now();
        $player->save();

        return $player->fresh();
    }

    /**
     * Update player tournament statistics
     */
    public function updateTournamentStatistics(Player $player, array $tournamentData): Player
    {
        $player->increment('total_tournaments');
        
        if ($tournamentData['won'] ?? false) {
            $player->increment('tournaments_won');
        }

        if (isset($tournamentData['final_position'])) {
            $achievements = $player->achievements ?? [];
            
            // Add achievement for top 3 finishes
            if ($tournamentData['final_position'] <= 3) {
                $achievements[] = [
                    'title' => $this->getPositionTitle($tournamentData['final_position']),
                    'description' => "Finished {$this->getOrdinal($tournamentData['final_position'])} in {$tournamentData['tournament_name']}",
                    'date_earned' => now()->toISOString(),
                    'tournament_id' => $tournamentData['tournament_id'],
                ];
            }

            $player->achievements = $achievements;
        }

        $player->save();
        return $player->fresh();
    }

    /**
     * Get players with upcoming matches
     */
    public function getWithUpcomingMatches(): Collection
    {
        return Player::whereHas('matches', function ($query) {
            $query->where('scheduled_at', '>', now())
                  ->where('status', 'scheduled');
        })->with(['matches' => function ($query) {
            $query->where('scheduled_at', '>', now())
                  ->where('status', 'scheduled')
                  ->orderBy('scheduled_at');
        }])->get();
    }

    /**
     * Get player ranking
     */
    public function getPlayerRanking(Player $player): int
    {
        return Player::where('skill_rating', '>', $player->skill_rating)->count() + 1;
    }

    /**
     * Get players by date range (registration date)
     */
    public function getByDateRange(\DateTime $startDate, \DateTime $endDate): Collection
    {
        return Player::whereBetween('created_at', [$startDate, $endDate])->get();
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
                case 'gender':
                    $query->where('gender', $value);
                    break;
                    
                case 'country':
                    $query->where('country', $value);
                    break;
                    
                case 'city':
                    $query->where('city', $value);
                    break;
                    
                case 'verification_status':
                    $query->where('verification_status', $value);
                    break;
                    
                case 'skill_rating_min':
                    $query->where('skill_rating', '>=', $value);
                    break;
                    
                case 'skill_rating_max':
                    $query->where('skill_rating', '<=', $value);
                    break;
                    
                case 'looking_for_partner':
                    $query->where('looking_for_partner', $value);
                    break;
                    
                case 'is_active':
                    if ($value) {
                        $query->where('last_active_at', '>=', now()->subDays(30));
                    }
                    break;
                    
                case 'search':
                    $query->where(function ($q) use ($value) {
                        $q->where('name', 'LIKE', "%{$value}%")
                          ->orWhere('email', 'LIKE', "%{$value}%");
                    });
                    break;
            }
        }

        return $query;
    }

    /**
     * Calculate skill level based on rating
     */
    private function calculateSkillLevel(int $rating): string
    {
        if ($rating < 1000) return 'beginner';
        if ($rating < 1500) return 'intermediate';
        if ($rating < 2000) return 'advanced';
        if ($rating < 2500) return 'expert';
        return 'professional';
    }

    /**
     * Get position title for achievements
     */
    private function getPositionTitle(int $position): string
    {
        return match($position) {
            1 => 'Tournament Champion',
            2 => 'Tournament Runner-up',
            3 => 'Tournament Bronze',
            default => 'Top Finisher'
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
} 