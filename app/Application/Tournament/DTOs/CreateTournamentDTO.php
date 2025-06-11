<?php

namespace App\Application\Tournament\DTOs;

use Carbon\Carbon;

class CreateTournamentDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $description,
        public readonly string $type,
        public readonly string $format,
        public readonly int $maxParticipants,
        public readonly int $minParticipants,
        public readonly ?Carbon $registrationStartDate,
        public readonly ?Carbon $registrationEndDate,
        public readonly ?Carbon $tournamentStartDate,
        public readonly ?Carbon $tournamentEndDate,
        public readonly ?float $entryFee,
        public readonly ?array $rules,
        public readonly ?string $venue,
        public readonly ?array $prizes,
        public readonly int $organizerId,
        public readonly ?array $settings = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            description: $data['description'],
            type: $data['type'],
            format: $data['format'],
            maxParticipants: $data['max_participants'],
            minParticipants: $data['min_participants'],
            registrationStartDate: isset($data['registration_start_date']) 
                ? Carbon::parse($data['registration_start_date']) 
                : null,
            registrationEndDate: isset($data['registration_end_date']) 
                ? Carbon::parse($data['registration_end_date']) 
                : null,
            tournamentStartDate: isset($data['tournament_start_date']) 
                ? Carbon::parse($data['tournament_start_date']) 
                : null,
            tournamentEndDate: isset($data['tournament_end_date']) 
                ? Carbon::parse($data['tournament_end_date']) 
                : null,
            entryFee: $data['entry_fee'] ?? null,
            rules: $data['rules'] ?? null,
            venue: $data['venue'] ?? null,
            prizes: $data['prizes'] ?? null,
            organizerId: $data['organizer_id'],
            settings: $data['settings'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type,
            'format' => $this->format,
            'max_participants' => $this->maxParticipants,
            'min_participants' => $this->minParticipants,
            'registration_start_date' => $this->registrationStartDate?->toDateTimeString(),
            'registration_end_date' => $this->registrationEndDate?->toDateTimeString(),
            'tournament_start_date' => $this->tournamentStartDate?->toDateTimeString(),
            'tournament_end_date' => $this->tournamentEndDate?->toDateTimeString(),
            'entry_fee' => $this->entryFee,
            'rules' => $this->rules,
            'venue' => $this->venue,
            'prizes' => $this->prizes,
            'organizer_id' => $this->organizerId,
            'settings' => $this->settings,
        ];
    }
} 