<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Tournament\Models\Tournament;
use App\Domain\Tournament\Repositories\TournamentRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class EloquentTournamentRepository implements TournamentRepositoryInterface
{
    protected Tournament $model;

    public function __construct(Tournament $model)
    {
        $this->model = $model;
    }

    public function getAll(array $filters = [], array $with = []): Collection
    {
        $query = $this->model->newQuery();

        if (!empty($with)) {
            $query->with($with);
        }

        $query = $this->applyFilters($query, $filters);

        return $query->get();
    }

    public function getPaginated(int $perPage = 15, array $filters = [], array $with = []): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        if (!empty($with)) {
            $query->with($with);
        }

        $query = $this->applyFilters($query, $filters);

        return $query->paginate($perPage);
    }

    public function findById(int $id, array $with = []): ?Tournament
    {
        $query = $this->model->newQuery();

        if (!empty($with)) {
            $query->with($with);
        }

        return $query->find($id);
    }

    public function findBySlug(string $slug, array $with = []): ?Tournament
    {
        $query = $this->model->newQuery();

        if (!empty($with)) {
            $query->with($with);
        }

        return $query->where('slug', $slug)->first();
    }

    public function create(array $data): Tournament
    {
        return $this->model->create($data);
    }

    public function update(Tournament $tournament, array $data): Tournament
    {
        $tournament->update($data);
        return $tournament->fresh();
    }

    public function delete(Tournament $tournament): bool
    {
        return $tournament->delete();
    }

    public function getByType(string $type, array $with = []): Collection
    {
        $query = $this->model->newQuery();

        if (!empty($with)) {
            $query->with($with);
        }

        return $query->byType($type)->get();
    }

    public function getByStatus(string $status, array $with = []): Collection
    {
        $query = $this->model->newQuery();

        if (!empty($with)) {
            $query->with($with);
        }

        return $query->byStatus($status)->get();
    }

    public function getUpcoming(array $with = []): Collection
    {
        $query = $this->model->newQuery();

        if (!empty($with)) {
            $query->with($with);
        }

        return $query->upcoming()->orderBy('tournament_start_date')->get();
    }

    public function getActive(array $with = []): Collection
    {
        $query = $this->model->newQuery();

        if (!empty($with)) {
            $query->with($with);
        }

        return $query->active()->get();
    }

    public function getCompleted(array $with = []): Collection
    {
        $query = $this->model->newQuery();

        if (!empty($with)) {
            $query->with($with);
        }

        return $query->completed()->orderBy('tournament_end_date', 'desc')->get();
    }

    public function getByOrganizer(int $organizerId, array $with = []): Collection
    {
        $query = $this->model->newQuery();

        if (!empty($with)) {
            $query->with($with);
        }

        return $query->where('organizer_id', $organizerId)
                    ->orderBy('created_at', 'desc')
                    ->get();
    }

    public function search(string $query, array $filters = []): Collection
    {
        $queryBuilder = $this->model->newQuery();

        // Search in name and description
        $queryBuilder->where(function ($q) use ($query) {
            $q->where('name', 'like', "%{$query}%")
              ->orWhere('description', 'like', "%{$query}%");
        });

        $queryBuilder = $this->applyFilters($queryBuilder, $filters);

        return $queryBuilder->orderBy('created_at', 'desc')->get();
    }

    public function getWithOpenRegistration(): Collection
    {
        return $this->model->where('status', Tournament::STATUS_REGISTRATION_OPEN)
                          ->where('registration_start_date', '<=', now())
                          ->where('registration_end_date', '>=', now())
                          ->whereColumn('current_participants', '<', 'max_participants')
                          ->orderBy('registration_end_date')
                          ->get();
    }

    public function getFeatured(int $limit = 10): Collection
    {
        return $this->model->whereIn('status', [
                              Tournament::STATUS_REGISTRATION_OPEN,
                              Tournament::STATUS_IN_PROGRESS
                          ])
                          ->orderByDesc('created_at')
                          ->limit($limit)
                          ->get();
    }

    public function getByDateRange(\DateTime $startDate, \DateTime $endDate): Collection
    {
        return $this->model->whereBetween('tournament_start_date', [$startDate, $endDate])
                          ->orderBy('tournament_start_date')
                          ->get();
    }

    public function getStatistics(): array
    {
        $totalTournaments = $this->model->count();
        $activeTournaments = $this->model->active()->count();
        $completedTournaments = $this->model->completed()->count();
        $upcomingTournaments = $this->model->upcoming()->count();

        $totalParticipants = $this->model->sum('current_participants');
        $averageParticipants = $totalTournaments > 0 ? $totalParticipants / $totalTournaments : 0;

        return [
            'total_tournaments' => $totalTournaments,
            'active_tournaments' => $activeTournaments,
            'completed_tournaments' => $completedTournaments,
            'upcoming_tournaments' => $upcomingTournaments,
            'total_participants' => $totalParticipants,
            'average_participants_per_tournament' => round($averageParticipants, 2),
        ];
    }

    public function countByStatus(): array
    {
        return $this->model->selectRaw('status, COUNT(*) as count')
                          ->groupBy('status')
                          ->pluck('count', 'status')
                          ->toArray();
    }

    public function getRecent(int $limit = 10): Collection
    {
        return $this->model->orderBy('created_at', 'desc')
                          ->limit($limit)
                          ->get();
    }

    /**
     * Apply filters to query
     */
    protected function applyFilters($query, array $filters)
    {
        foreach ($filters as $filter => $value) {
            if (is_null($value)) {
                continue;
            }

            switch ($filter) {
                case 'type':
                    $query->where('type', $value);
                    break;

                case 'status':
                    $query->where('status', $value);
                    break;

                case 'format':
                    $query->where('format', $value);
                    break;

                case 'organizer_id':
                    $query->where('organizer_id', $value);
                    break;

                case 'min_participants':
                    $query->where('current_participants', '>=', $value);
                    break;

                case 'max_participants':
                    $query->where('current_participants', '<=', $value);
                    break;

                case 'entry_fee_min':
                    $query->where('entry_fee', '>=', $value);
                    break;

                case 'entry_fee_max':
                    $query->where('entry_fee', '<=', $value);
                    break;

                case 'start_date_from':
                    $query->whereDate('tournament_start_date', '>=', $value);
                    break;

                case 'start_date_to':
                    $query->whereDate('tournament_start_date', '<=', $value);
                    break;

                case 'venue':
                    $query->where('venue', 'like', "%{$value}%");
                    break;

                case 'search':
                    $query->where(function ($q) use ($value) {
                        $q->where('name', 'like', "%{$value}%")
                          ->orWhere('description', 'like', "%{$value}%");
                    });
                    break;
            }
        }

        return $query;
    }
} 