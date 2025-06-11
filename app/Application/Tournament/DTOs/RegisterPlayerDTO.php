<?php

namespace App\Application\Tournament\DTOs;

class RegisterPlayerDTO
{
    public function __construct(
        public readonly int $tournamentId,
        public readonly int $playerId,
        public readonly ?int $teamId = null,
        public readonly ?int $seed = null,
        public readonly ?string $notes = null,
        public readonly ?string $paymentStatus = null,
        public readonly ?array $emergencyContact = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            tournamentId: $data['tournament_id'],
            playerId: $data['player_id'],
            teamId: $data['team_id'] ?? null,
            seed: $data['seed'] ?? null,
            notes: $data['notes'] ?? null,
            paymentStatus: $data['payment_status'] ?? null,
            emergencyContact: $data['emergency_contact'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'tournament_id' => $this->tournamentId,
            'player_id' => $this->playerId,
            'team_id' => $this->teamId,
            'seed' => $this->seed,
            'notes' => $this->notes,
            'payment_status' => $this->paymentStatus,
            'emergency_contact' => $this->emergencyContact,
        ];
    }
} 