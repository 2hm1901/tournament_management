<?php

namespace App\Domain\Team\Repositories;

use App\Domain\Tournament\Models\Team;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface TeamRepositoryInterface
{
    /**
     * Get all teams with optional filters
     */
    public function getAll(array $filters = [], array $with = []): Collection;

    /**
     * Get paginated teams
     */
    public function getPaginated(int $perPage = 15, array $filters = [], array $with = []): LengthAwarePaginator;

    /**
     * Find team by ID
     */
    public function findById(int $id, array $with = []): ?Team;

    /**
     * Find team by players
     */
    public function findByPlayers(int $player1Id, int $player2Id, array $with = []): ?Team;

    /**
     * Create a new team
     */
    public function create(array $data): Team;

    /**
     * Update team
     */
    public function update(Team $team, array $data): Team;

    /**
     * Delete team
     */
    public function delete(Team $team): bool;

    /**
     * Get teams by player
     */
    public function getByPlayer(int $playerId, array $with = []): Collection;

    /**
     * Get active teams by player
     */
    public function getActiveByPlayer(int $playerId, array $with = []): Collection;

    /**
     * Get teams by captain
     */
    public function getByCaptain(int $captainId, array $with = []): Collection;

    /**
     * Get teams by status
     */
    public function getByStatus(string $status, array $with = []): Collection;

    /**
     * Get teams by tournament
     */
    public function getByTournament(int $tournamentId, array $with = []): Collection;

    /**
     * Get top rated teams
     */
    public function getTopRated(int $limit = 10, array $with = []): Collection;

    /**
     * Get recent teams
     */
    public function getRecent(int $limit = 10, array $with = []): Collection;

    /**
     * Search teams by name
     */
    public function search(string $query, array $filters = []): Collection;

    /**
     * Get teams available for tournament
     */
    public function getAvailableForTournament(int $tournamentId): Collection;

    /**
     * Get team statistics
     */
    public function getStatistics(): array;

    /**
     * Count teams by status
     */
    public function countByStatus(): array;

    /**
     * Update team rating
     */
    public function updateTeamRating(Team $team, int $newRating, array $matchData = []): Team;

    /**
     * Update team statistics after match
     */
    public function updateMatchStatistics(Team $team, array $matchData): Team;

    /**
     * Update team tournament statistics
     */
    public function updateTournamentStatistics(Team $team, array $tournamentData): Team;
} 