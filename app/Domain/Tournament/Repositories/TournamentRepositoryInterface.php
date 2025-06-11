<?php

namespace App\Domain\Tournament\Repositories;

use App\Domain\Tournament\Models\Tournament;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface TournamentRepositoryInterface
{
    /**
     * Get all tournaments with optional filters
     */
    public function getAll(array $filters = [], array $with = []): Collection;

    /**
     * Get paginated tournaments
     */
    public function getPaginated(int $perPage = 15, array $filters = [], array $with = []): LengthAwarePaginator;

    /**
     * Find tournament by ID
     */
    public function findById(int $id, array $with = []): ?Tournament;

    /**
     * Find tournament by slug
     */
    public function findBySlug(string $slug, array $with = []): ?Tournament;

    /**
     * Create a new tournament
     */
    public function create(array $data): Tournament;

    /**
     * Update tournament
     */
    public function update(Tournament $tournament, array $data): Tournament;

    /**
     * Delete tournament
     */
    public function delete(Tournament $tournament): bool;

    /**
     * Get tournaments by type
     */
    public function getByType(string $type, array $with = []): Collection;

    /**
     * Get tournaments by status
     */
    public function getByStatus(string $status, array $with = []): Collection;

    /**
     * Get upcoming tournaments
     */
    public function getUpcoming(array $with = []): Collection;

    /**
     * Get active tournaments (in progress)
     */
    public function getActive(array $with = []): Collection;

    /**
     * Get completed tournaments
     */
    public function getCompleted(array $with = []): Collection;

    /**
     * Get tournaments by organizer
     */
    public function getByOrganizer(int $organizerId, array $with = []): Collection;

    /**
     * Search tournaments by name or description
     */
    public function search(string $query, array $filters = []): Collection;

    /**
     * Get tournaments with registration open
     */
    public function getWithOpenRegistration(): Collection;

    /**
     * Get featured tournaments
     */
    public function getFeatured(int $limit = 10): Collection;

    /**
     * Get tournaments by date range
     */
    public function getByDateRange(\DateTime $startDate, \DateTime $endDate): Collection;

    /**
     * Get tournament statistics
     */
    public function getStatistics(): array;

    /**
     * Count tournaments by status
     */
    public function countByStatus(): array;

    /**
     * Get recent tournaments
     */
    public function getRecent(int $limit = 10): Collection;
} 