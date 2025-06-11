<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Tournament\Models\TournamentParticipant;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class TournamentParticipantRepository
{
    /**
     * Get all participants with optional filters
     */
    public function getAll(array $filters = [], array $with = []): Collection
    {
        return $this->applyFilters(TournamentParticipant::query(), $filters)
            ->with($with)
            ->get();
    }

    /**
     * Get paginated participants
     */
    public function getPaginated(int $perPage = 15, array $filters = [], array $with = []): LengthAwarePaginator
    {
        return $this->applyFilters(TournamentParticipant::query(), $filters)
            ->with($with)
            ->paginate($perPage);
    }

    /**
     * Find participant by ID
     */
    public function findById(int $id, array $with = []): ?TournamentParticipant
    {
        return TournamentParticipant::with($with)->find($id);
    }

    /**
     * Find participant by tournament and player
     */
    public function findByTournamentAndPlayer(int $tournamentId, int $playerId, array $with = []): ?TournamentParticipant
    {
        return TournamentParticipant::with($with)
            ->where('tournament_id', $tournamentId)
            ->where('player_id', $playerId)
            ->first();
    }

    /**
     * Find participant by tournament and team
     */
    public function findByTournamentAndTeam(int $tournamentId, int $teamId, array $with = []): ?TournamentParticipant
    {
        return TournamentParticipant::with($with)
            ->where('tournament_id', $tournamentId)
            ->where('team_id', $teamId)
            ->first();
    }

    /**
     * Create a new participant
     */
    public function create(array $data): TournamentParticipant
    {
        return TournamentParticipant::create($data);
    }

    /**
     * Update participant
     */
    public function update(TournamentParticipant $participant, array $data): TournamentParticipant
    {
        $participant->update($data);
        return $participant->fresh();
    }

    /**
     * Delete participant
     */
    public function delete(TournamentParticipant $participant): bool
    {
        return $participant->delete();
    }

    /**
     * Get participants by tournament
     */
    public function getByTournament(int $tournamentId, array $with = []): Collection
    {
        return TournamentParticipant::with($with)
            ->where('tournament_id', $tournamentId)
            ->get();
    }

    /**
     * Get participants by player
     */
    public function getByPlayer(int $playerId, array $with = []): Collection
    {
        return TournamentParticipant::with($with)
            ->where('player_id', $playerId)
            ->get();
    }

    /**
     * Get participants by team
     */
    public function getByTeam(int $teamId, array $with = []): Collection
    {
        return TournamentParticipant::with($with)
            ->where('team_id', $teamId)
            ->get();
    }

    /**
     * Get participants by status
     */
    public function getByStatus(string $status, array $with = []): Collection
    {
        return TournamentParticipant::with($with)
            ->where('status', $status)
            ->get();
    }

    /**
     * Get confirmed participants for tournament
     */
    public function getConfirmedByTournament(int $tournamentId, array $with = []): Collection
    {
        return TournamentParticipant::with($with)
            ->where('tournament_id', $tournamentId)
            ->where('status', TournamentParticipant::STATUS_CONFIRMED)
            ->get();
    }

    /**
     * Get pending participants for tournament
     */
    public function getPendingByTournament(int $tournamentId, array $with = []): Collection
    {
        return TournamentParticipant::with($with)
            ->where('tournament_id', $tournamentId)
            ->where('status', TournamentParticipant::STATUS_PENDING)
            ->get();
    }

    /**
     * Get participants with paid fees
     */
    public function getPaidParticipants(int $tournamentId = null, array $with = []): Collection
    {
        $query = TournamentParticipant::with($with)
            ->where('payment_status', TournamentParticipant::PAYMENT_PAID);

        if ($tournamentId) {
            $query->where('tournament_id', $tournamentId);
        }

        return $query->get();
    }

    /**
     * Get participants with pending payments
     */
    public function getPendingPayments(int $tournamentId = null, array $with = []): Collection
    {
        $query = TournamentParticipant::with($with)
            ->where('payment_status', TournamentParticipant::PAYMENT_PENDING);

        if ($tournamentId) {
            $query->where('tournament_id', $tournamentId);
        }

        return $query->get();
    }

    /**
     * Get seeded participants for tournament
     */
    public function getSeededByTournament(int $tournamentId, array $with = []): Collection
    {
        return TournamentParticipant::with($with)
            ->where('tournament_id', $tournamentId)
            ->whereNotNull('seed')
            ->orderBy('seed')
            ->get();
    }

    /**
     * Get unseeded participants for tournament
     */
    public function getUnseededByTournament(int $tournamentId, array $with = []): Collection
    {
        return TournamentParticipant::with($with)
            ->where('tournament_id', $tournamentId)
            ->whereNull('seed')
            ->get();
    }

    /**
     * Get participants with final positions
     */
    public function getWithFinalPositions(int $tournamentId, array $with = []): Collection
    {
        return TournamentParticipant::with($with)
            ->where('tournament_id', $tournamentId)
            ->whereNotNull('final_position')
            ->orderBy('final_position')
            ->get();
    }

    /**
     * Get participants who won prize money
     */
    public function getPrizeWinners(int $tournamentId, array $with = []): Collection
    {
        return TournamentParticipant::with($with)
            ->where('tournament_id', $tournamentId)
            ->where('prize_money', '>', 0)
            ->orderBy('prize_money', 'desc')
            ->get();
    }

    /**
     * Count participants by tournament
     */
    public function countByTournament(int $tournamentId): int
    {
        return TournamentParticipant::where('tournament_id', $tournamentId)->count();
    }

    /**
     * Count confirmed participants by tournament
     */
    public function countConfirmedByTournament(int $tournamentId): int
    {
        return TournamentParticipant::where('tournament_id', $tournamentId)
            ->where('status', TournamentParticipant::STATUS_CONFIRMED)
            ->count();
    }

    /**
     * Count participants by status
     */
    public function countByStatus(int $tournamentId = null): array
    {
        $query = TournamentParticipant::selectRaw('status, COUNT(*) as count')
            ->groupBy('status');

        if ($tournamentId) {
            $query->where('tournament_id', $tournamentId);
        }

        return $query->pluck('count', 'status')->toArray();
    }

    /**
     * Get participant statistics
     */
    public function getStatistics(int $tournamentId = null): array
    {
        $query = TournamentParticipant::query();

        if ($tournamentId) {
            $query->where('tournament_id', $tournamentId);
        }

        $participants = $query->get();

        return [
            'total_participants' => $participants->count(),
            'confirmed_participants' => $participants->where('status', TournamentParticipant::STATUS_CONFIRMED)->count(),
            'pending_participants' => $participants->where('status', TournamentParticipant::STATUS_PENDING)->count(),
            'rejected_participants' => $participants->where('status', TournamentParticipant::STATUS_REJECTED)->count(),
            'withdrawn_participants' => $participants->where('status', TournamentParticipant::STATUS_WITHDRAWN)->count(),
            'disqualified_participants' => $participants->where('status', TournamentParticipant::STATUS_DISQUALIFIED)->count(),
            'eliminated_participants' => $participants->where('status', TournamentParticipant::STATUS_ELIMINATED)->count(),
            'paid_participants' => $participants->where('payment_status', TournamentParticipant::PAYMENT_PAID)->count(),
            'pending_payments' => $participants->where('payment_status', TournamentParticipant::PAYMENT_PENDING)->count(),
            'total_prize_money' => $participants->sum('prize_money'),
            'seeded_participants' => $participants->whereNotNull('seed')->count(),
            'average_seed' => $participants->whereNotNull('seed')->avg('seed'),
        ];
    }

    /**
     * Bulk update participants status
     */
    public function bulkUpdateStatus(array $participantIds, string $status): int
    {
        return TournamentParticipant::whereIn('id', $participantIds)
            ->update(['status' => $status, 'updated_at' => now()]);
    }

    /**
     * Bulk confirm participants
     */
    public function bulkConfirm(array $participantIds): int
    {
        return $this->bulkUpdateStatus($participantIds, TournamentParticipant::STATUS_CONFIRMED);
    }

    /**
     * Bulk reject participants
     */
    public function bulkReject(array $participantIds): int
    {
        return $this->bulkUpdateStatus($participantIds, TournamentParticipant::STATUS_REJECTED);
    }

    /**
     * Assign seeds to participants
     */
    public function assignSeeds(int $tournamentId, array $seedAssignments): bool
    {
        foreach ($seedAssignments as $participantId => $seed) {
            TournamentParticipant::where('id', $participantId)
                ->where('tournament_id', $tournamentId)
                ->update(['seed' => $seed]);
        }

        return true;
    }

    /**
     * Auto-assign seeds based on player ratings
     */
    public function autoAssignSeeds(int $tournamentId): bool
    {
        $participants = TournamentParticipant::where('tournament_id', $tournamentId)
            ->where('status', TournamentParticipant::STATUS_CONFIRMED)
            ->with('player')
            ->get()
            ->sortByDesc(function ($participant) {
                return $participant->player->skill_rating;
            });

        $seed = 1;
        foreach ($participants as $participant) {
            $participant->update(['seed' => $seed]);
            $seed++;
        }

        return true;
    }

    /**
     * Get available seeds for tournament
     */
    public function getAvailableSeeds(int $tournamentId): array
    {
        $usedSeeds = TournamentParticipant::where('tournament_id', $tournamentId)
            ->whereNotNull('seed')
            ->pluck('seed')
            ->toArray();

        $maxParticipants = TournamentParticipant::where('tournament_id', $tournamentId)->count();
        $allSeeds = range(1, $maxParticipants);

        return array_diff($allSeeds, $usedSeeds);
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
                case 'tournament_id':
                    $query->where('tournament_id', $value);
                    break;
                    
                case 'player_id':
                    $query->where('player_id', $value);
                    break;
                    
                case 'team_id':
                    $query->where('team_id', $value);
                    break;
                    
                case 'status':
                    $query->where('status', $value);
                    break;
                    
                case 'payment_status':
                    $query->where('payment_status', $value);
                    break;
                    
                case 'has_seed':
                    if ($value) {
                        $query->whereNotNull('seed');
                    } else {
                        $query->whereNull('seed');
                    }
                    break;
                    
                case 'has_prize_money':
                    if ($value) {
                        $query->where('prize_money', '>', 0);
                    } else {
                        $query->where('prize_money', '<=', 0);
                    }
                    break;
                    
                case 'registration_date_from':
                    $query->where('registration_date', '>=', $value);
                    break;
                    
                case 'registration_date_to':
                    $query->where('registration_date', '<=', $value);
                    break;
            }
        }

        return $query;
    }
} 