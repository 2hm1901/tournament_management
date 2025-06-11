<?php

namespace App\Application\Tournament\DTOs;

use Carbon\Carbon;

class TournamentFilterDTO
{
    public function __construct(
        public readonly ?string $type = null,
        public readonly ?string $status = null,
        public readonly ?int $organizerId = null,
        public readonly ?string $search = null,
        public readonly ?Carbon $dateFrom = null,
        public readonly ?Carbon $dateTo = null,
        public readonly ?bool $hasOpenRegistration = null,
        public readonly ?bool $isFeatured = null,
        public readonly ?string $venue = null,
        public readonly ?int $minParticipants = null,
        public readonly ?int $maxParticipants = null,
        public readonly ?float $entryFeeMin = null,
        public readonly ?float $entryFeeMax = null,
        public readonly int $page = 1,
        public readonly int $perPage = 15,
        public readonly string $sortBy = 'created_at',
        public readonly string $sortDirection = 'desc',
        public readonly array $relationships = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            type: $data['type'] ?? null,
            status: $data['status'] ?? null,
            organizerId: isset($data['organizer_id']) ? (int) $data['organizer_id'] : null,
            search: $data['search'] ?? null,
            dateFrom: isset($data['date_from']) ? Carbon::parse($data['date_from']) : null,
            dateTo: isset($data['date_to']) ? Carbon::parse($data['date_to']) : null,
            hasOpenRegistration: isset($data['has_open_registration']) 
                ? filter_var($data['has_open_registration'], FILTER_VALIDATE_BOOLEAN) 
                : null,
            isFeatured: isset($data['is_featured']) 
                ? filter_var($data['is_featured'], FILTER_VALIDATE_BOOLEAN) 
                : null,
            venue: $data['venue'] ?? null,
            minParticipants: isset($data['min_participants']) ? (int) $data['min_participants'] : null,
            maxParticipants: isset($data['max_participants']) ? (int) $data['max_participants'] : null,
            entryFeeMin: isset($data['entry_fee_min']) ? (float) $data['entry_fee_min'] : null,
            entryFeeMax: isset($data['entry_fee_max']) ? (float) $data['entry_fee_max'] : null,
            page: isset($data['page']) ? (int) $data['page'] : 1,
            perPage: isset($data['per_page']) ? (int) $data['per_page'] : 15,
            sortBy: $data['sort_by'] ?? 'created_at',
            sortDirection: $data['sort_direction'] ?? 'desc',
            relationships: $data['with'] ?? [],
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'type' => $this->type,
            'status' => $this->status,
            'organizer_id' => $this->organizerId,
            'search' => $this->search,
            'date_from' => $this->dateFrom?->toDateString(),
            'date_to' => $this->dateTo?->toDateString(),
            'has_open_registration' => $this->hasOpenRegistration,
            'is_featured' => $this->isFeatured,
            'venue' => $this->venue,
            'min_participants' => $this->minParticipants,
            'max_participants' => $this->maxParticipants,
            'entry_fee_min' => $this->entryFeeMin,
            'entry_fee_max' => $this->entryFeeMax,
            'page' => $this->page,
            'per_page' => $this->perPage,
            'sort_by' => $this->sortBy,
            'sort_direction' => $this->sortDirection,
            'with' => $this->relationships,
        ], fn($value) => $value !== null);
    }
} 