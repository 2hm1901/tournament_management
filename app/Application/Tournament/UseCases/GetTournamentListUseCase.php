<?php

namespace App\Application\Tournament\UseCases;

use App\Application\Tournament\DTOs\TournamentFilterDTO;
use App\Domain\Tournament\Repositories\TournamentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GetTournamentListUseCase
{
    public function __construct(
        private TournamentRepositoryInterface $tournamentRepository
    ) {}

    public function execute(TournamentFilterDTO $filterDTO): LengthAwarePaginator
    {
        $filters = [
            'type' => $filterDTO->type,
            'status' => $filterDTO->status,
            'organizer_id' => $filterDTO->organizerId,
            'search' => $filterDTO->search,
            'date_from' => $filterDTO->dateFrom?->toDateString(),
            'date_to' => $filterDTO->dateTo?->toDateString(),
            'has_open_registration' => $filterDTO->hasOpenRegistration,
            'is_featured' => $filterDTO->isFeatured,
            'venue' => $filterDTO->venue,
            'min_participants' => $filterDTO->minParticipants,
            'max_participants' => $filterDTO->maxParticipants,
            'entry_fee_min' => $filterDTO->entryFeeMin,
            'entry_fee_max' => $filterDTO->entryFeeMax,
        ];

        // Remove null values
        $filters = array_filter($filters, fn($value) => $value !== null);

        return $this->tournamentRepository->getPaginated(
            perPage: $filterDTO->perPage,
            filters: $filters,
            with: $filterDTO->relationships
        );
    }
} 