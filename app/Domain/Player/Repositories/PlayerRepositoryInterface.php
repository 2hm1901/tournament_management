<?php

namespace App\Domain\Player\Repositories;

use App\Domain\Player\Models\Player;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface PlayerRepositoryInterface
{
    /**
     * Get all players with optional filters
     */
    public function getAll(array $filters = [], array $with = []): Collection;

    /**
     * Get paginated players
     */
    public function getPaginated(int $perPage = 15, array $filters = [], array $with = []): LengthAwarePaginator;

    /**
     * Find player by ID
     */
    public function findById(int $id, array $with = []): ?Player;

    /**
     * Find player by email
     */
    public function findByEmail(string $email, array $with = []): ?Player;

    /**
     * Create a new player
     */
    public function create(array $data): Player;

    /**
     * Update player
     */
    public function update(Player $player, array $data): Player;

    /**
     * Delete player
     */
    public function delete(Player $player): bool;

    /**
     * Get players by gender
     */
    public function getByGender(string $gender, array $with = []): Collection;

    /**
     * Get players by skill level range
     */
    public function getBySkillRange(int $minRating, int $maxRating, array $with = []): Collection;

    /**
     * Get players by country
     */
    public function getByCountry(string $country, array $with = []): Collection;

    /**
     * Get players by city
     */
    public function getByCity(string $city, array $with = []): Collection;

    /**
     * Search players by name or email
     */
    public function search(string $query, array $filters = []): Collection;

    /**
     * Get verified players
     */
    public function getVerified(array $with = []): Collection;

    /**
     * Get players with highest ratings
     */
    public function getTopRated(int $limit = 10, array $with = []): Collection;

    /**
     * Get recent players (recently joined)
     */
    public function getRecent(int $limit = 10, array $with = []): Collection;

    /**
     * Get active players (recently active)
     */
    public function getActive(array $with = []): Collection;

    /**
     * Get players available for tournaments
     */
    public function getAvailableForTournament(int $tournamentId): Collection;

    /**
     * Get players by tournament participation
     */
    public function getByTournament(int $tournamentId, array $with = []): Collection;

    /**
     * Get players looking for partners (for doubles)
     */
    public function getLookingForPartners(string $gender = null): Collection;

    /**
     * Get player statistics
     */
    public function getStatistics(): array;

    /**
     * Count players by verification status
     */
    public function countByVerificationStatus(): array;

    /**
     * Count players by skill level
     */
    public function countBySkillLevel(): array;

    /**
     * Update player skill rating
     */
    public function updateSkillRating(Player $player, int $newRating, array $matchData = []): Player;

    /**
     * Update player statistics after match
     */
    public function updateMatchStatistics(Player $player, array $matchData): Player;

    /**
     * Update player tournament statistics
     */
    public function updateTournamentStatistics(Player $player, array $tournamentData): Player;

    /**
     * Get players with upcoming matches
     */
    public function getWithUpcomingMatches(): Collection;

    /**
     * Get player ranking
     */
    public function getPlayerRanking(Player $player): int;

    /**
     * Get players by date range (registration date)
     */
    public function getByDateRange(\DateTime $startDate, \DateTime $endDate): Collection;
} 