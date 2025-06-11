<?php

namespace App\Application\Tournament\UseCases;

use App\Application\Tournament\DTOs\CreateTournamentDTO;
use App\Domain\Tournament\Models\Tournament;
use App\Domain\Tournament\Repositories\TournamentRepositoryInterface;
use Illuminate\Support\Str;

class CreateTournamentUseCase
{
    public function __construct(
        private TournamentRepositoryInterface $tournamentRepository
    ) {}

    public function execute(CreateTournamentDTO $dto): Tournament
    {
        // Validate business rules
        $this->validateTournamentData($dto);

        // Prepare tournament data
        $tournamentData = [
            'name' => $dto->name,
            'description' => $dto->description,
            'slug' => $this->generateUniqueSlug($dto->name),
            'type' => $dto->type,
            'format' => $dto->format,
            'status' => Tournament::STATUS_DRAFT,
            'max_participants' => $dto->maxParticipants,
            'min_participants' => $dto->minParticipants,
            'registration_start_date' => $dto->registrationStartDate,
            'registration_end_date' => $dto->registrationEndDate,
            'tournament_start_date' => $dto->tournamentStartDate,
            'tournament_end_date' => $dto->tournamentEndDate,
            'entry_fee' => $dto->entryFee,
            'rules' => $dto->rules,
            'venue' => $dto->venue,
            'prizes' => $dto->prizes,
            'organizer_id' => $dto->organizerId,
            'settings' => $dto->settings,
        ];

        // Create tournament
        $tournament = $this->tournamentRepository->create($tournamentData);

        // TODO: Dispatch TournamentCreated event

        return $tournament;
    }

    private function validateTournamentData(CreateTournamentDTO $dto): void
    {
        // Business rule validations
        if ($dto->minParticipants >= $dto->maxParticipants) {
            throw new \InvalidArgumentException('Min participants must be less than max participants');
        }

        if ($dto->registrationStartDate && $dto->registrationEndDate) {
            if ($dto->registrationStartDate >= $dto->registrationEndDate) {
                throw new \InvalidArgumentException('Registration start date must be before end date');
            }
        }

        if ($dto->tournamentStartDate && $dto->tournamentEndDate) {
            if ($dto->tournamentStartDate >= $dto->tournamentEndDate) {
                throw new \InvalidArgumentException('Tournament start date must be before end date');
            }
        }

        if ($dto->registrationEndDate && $dto->tournamentStartDate) {
            if ($dto->registrationEndDate > $dto->tournamentStartDate) {
                throw new \InvalidArgumentException('Registration must end before tournament starts');
            }
        }

        // Validate tournament type and format compatibility
        $this->validateTypeFormatCompatibility($dto->type, $dto->format);
    }

    private function validateTypeFormatCompatibility(string $type, string $format): void
    {
        // Add specific business rules for type-format combinations
        $validCombinations = [
            Tournament::TYPE_MEN_SINGLES => [
                Tournament::FORMAT_SINGLE_ELIMINATION,
                Tournament::FORMAT_DOUBLE_ELIMINATION,
                Tournament::FORMAT_ROUND_ROBIN,
                Tournament::FORMAT_SWISS
            ],
            Tournament::TYPE_WOMEN_SINGLES => [
                Tournament::FORMAT_SINGLE_ELIMINATION,
                Tournament::FORMAT_DOUBLE_ELIMINATION,
                Tournament::FORMAT_ROUND_ROBIN,
                Tournament::FORMAT_SWISS
            ],
            Tournament::TYPE_MEN_DOUBLES => [
                Tournament::FORMAT_SINGLE_ELIMINATION,
                Tournament::FORMAT_DOUBLE_ELIMINATION,
                Tournament::FORMAT_ROUND_ROBIN
            ],
            Tournament::TYPE_WOMEN_DOUBLES => [
                Tournament::FORMAT_SINGLE_ELIMINATION,
                Tournament::FORMAT_DOUBLE_ELIMINATION,
                Tournament::FORMAT_ROUND_ROBIN
            ],
            Tournament::TYPE_MIXED_DOUBLES => [
                Tournament::FORMAT_SINGLE_ELIMINATION,
                Tournament::FORMAT_DOUBLE_ELIMINATION,
                Tournament::FORMAT_ROUND_ROBIN
            ],
        ];

        if (!isset($validCombinations[$type]) || !in_array($format, $validCombinations[$type])) {
            throw new \InvalidArgumentException("Tournament type '{$type}' is not compatible with format '{$format}'");
        }
    }

    private function generateUniqueSlug(string $name): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        // Ensure slug is unique
        while ($this->tournamentRepository->findBySlug($slug)) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
} 