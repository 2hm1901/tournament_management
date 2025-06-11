<?php

namespace App\Http\Resources\Tournament;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class TournamentCollection extends ResourceCollection
{
    /**
     * The resource that this resource collects.
     */
    public $collects = TournamentResource::class;

    /**
     * Transform the resource collection into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
            'meta' => [
                'pagination' => [
                    'current_page' => $this->currentPage(),
                    'per_page' => $this->perPage(),
                    'total' => $this->total(),
                    'last_page' => $this->lastPage(),
                    'from' => $this->firstItem(),
                    'to' => $this->lastItem(),
                    'has_more_pages' => $this->hasMorePages(),
                ],
                'filters_applied' => $this->getAppliedFilters($request),
                'summary' => [
                    'total_tournaments' => $this->total(),
                    'tournaments_on_page' => $this->count(),
                    'registration_open_count' => $this->countByRegistrationStatus(),
                    'tournament_types_distribution' => $this->getTypeDistribution(),
                ],
            ],
            'links' => [
                'first' => $this->url(1),
                'last' => $this->url($this->lastPage()),
                'prev' => $this->previousPageUrl(),
                'next' => $this->nextPageUrl(),
                'self' => $this->url($this->currentPage()),
            ],
        ];
    }

    /**
     * Get additional data that should be returned with the resource array.
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'api_version' => 'v1',
                'timestamp' => now()->toISOString(),
                'request_id' => $request->header('X-Request-ID', uniqid()),
                'available_filters' => [
                    'type' => ['men_singles', 'women_singles', 'men_doubles', 'women_doubles', 'mixed_doubles'],
                    'status' => ['draft', 'registration_open', 'registration_closed', 'in_progress', 'completed', 'cancelled'],
                    'has_open_registration' => [true, false],
                    'is_featured' => [true, false],
                ],
                'available_sorts' => [
                    'name' => 'Tournament Name',
                    'created_at' => 'Creation Date',
                    'tournament_start_date' => 'Start Date',
                    'entry_fee' => 'Entry Fee',
                    'max_participants' => 'Maximum Participants',
                ],
                'available_includes' => [
                    'organizer',
                    'participants',
                    'participants.player',
                    'participants.team',
                    'matches',
                ],
            ],
        ];
    }

    /**
     * Get applied filters from request.
     */
    private function getAppliedFilters(Request $request): array
    {
        $filters = [];
        
        $filterKeys = [
            'type', 'status', 'organizer_id', 'search', 'date_from', 'date_to',
            'has_open_registration', 'is_featured', 'venue', 'min_participants',
            'max_participants', 'entry_fee_min', 'entry_fee_max'
        ];

        foreach ($filterKeys as $key) {
            if ($request->has($key) && $request->get($key) !== null) {
                $filters[$key] = $request->get($key);
            }
        }

        return $filters;
    }

    /**
     * Count tournaments by registration status.
     */
    private function countByRegistrationStatus(): array
    {
        $openCount = 0;
        $closedCount = 0;

        foreach ($this->collection as $tournament) {
            if ($tournament->resource->isRegistrationOpen) {
                $openCount++;
            } else {
                $closedCount++;
            }
        }

        return [
            'open' => $openCount,
            'closed' => $closedCount,
        ];
    }

    /**
     * Get tournament type distribution.
     */
    private function getTypeDistribution(): array
    {
        $distribution = [];

        foreach ($this->collection as $tournament) {
            $type = $tournament->resource->type;
            $distribution[$type] = ($distribution[$type] ?? 0) + 1;
        }

        return $distribution;
    }

    /**
     * Customize the pagination information for the resource.
     */
    public function paginationInformation(Request $request, array $paginated, array $default): array
    {
        unset($default['links']);
        
        return [
            'meta' => array_merge($default['meta'], [
                'query_time' => number_format(microtime(true) - LARAVEL_START, 3) . 's',
                'cache_status' => $request->header('X-Cache-Status', 'miss'),
            ]),
        ];
    }
} 