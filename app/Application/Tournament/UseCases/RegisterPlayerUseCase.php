<?php

namespace App\Application\Tournament\UseCases;

use App\Application\Tournament\DTOs\RegisterPlayerDTO;
use App\Domain\Tournament\Models\Tournament;
use App\Domain\Tournament\Models\TournamentParticipant;
use App\Domain\Tournament\Repositories\TournamentRepositoryInterface;
use App\Domain\Player\Repositories\PlayerRepositoryInterface;
use App\Infrastructure\Repositories\TournamentParticipantRepository;

class RegisterPlayerUseCase
{
    public function __construct(
        private TournamentRepositoryInterface $tournamentRepository,
        private PlayerRepositoryInterface $playerRepository,
        private TournamentParticipantRepository $participantRepository
    ) {}

    public function execute(RegisterPlayerDTO $dto): TournamentParticipant
    {
        // Validate tournament exists and is open for registration
        $tournament = $this->tournamentRepository->findById($dto->tournamentId);
        if (!$tournament) {
            throw new \InvalidArgumentException('Tournament not found');
        }

        // Validate player exists
        $player = $this->playerRepository->findById($dto->playerId);
        if (!$player) {
            throw new \InvalidArgumentException('Player not found');
        }

        // Business rule validations
        $this->validateRegistration($tournament, $player, $dto);

        // Check if already registered
        $existingParticipant = $this->participantRepository->findByTournamentAndPlayer(
            $dto->tournamentId, 
            $dto->playerId
        );

        if ($existingParticipant) {
            throw new \InvalidArgumentException('Player is already registered for this tournament');
        }

        // Prepare participant data
        $participantData = [
            'tournament_id' => $dto->tournamentId,
            'player_id' => $dto->playerId,
            'team_id' => $dto->teamId,
            'status' => TournamentParticipant::STATUS_PENDING,
            'registration_date' => now(),
            'seed' => $dto->seed,
            'notes' => $dto->notes,
            'payment_status' => $dto->paymentStatus ?? TournamentParticipant::PAYMENT_PENDING,
            'payment_amount' => $tournament->entry_fee,
            'emergency_contact' => $dto->emergencyContact,
        ];

        // Create tournament participant
        $participant = $this->participantRepository->create($participantData);

        // TODO: Dispatch PlayerRegistered event
        // TODO: Send confirmation email
        // TODO: Process payment if required

        return $participant;
    }

    private function validateRegistration(Tournament $tournament, $player, RegisterPlayerDTO $dto): void
    {
        // Check if tournament registration is open
        if (!$tournament->canRegister()) {
            throw new \InvalidArgumentException('Tournament registration is not open');
        }

        // Check if tournament is full
        if ($tournament->isFull) {
            throw new \InvalidArgumentException('Tournament is full');
        }

        // Validate player eligibility for tournament type
        $this->validatePlayerEligibility($tournament, $player, $dto);

        // Validate team requirements for doubles tournaments
        if ($tournament->isDoubles && !$dto->teamId) {
            throw new \InvalidArgumentException('Team is required for doubles tournaments');
        }

        // Validate single tournaments don't have teams
        if (!$tournament->isDoubles && $dto->teamId) {
            throw new \InvalidArgumentException('Teams are not allowed for singles tournaments');
        }
    }

    private function validatePlayerEligibility(Tournament $tournament, $player, RegisterPlayerDTO $dto): void
    {
        // Gender-based tournament type validation
        switch ($tournament->type) {
            case Tournament::TYPE_MEN_SINGLES:
            case Tournament::TYPE_MEN_DOUBLES:
                if ($player->gender !== 'male') {
                    throw new \InvalidArgumentException('Only male players can register for men\'s tournaments');
                }
                break;

            case Tournament::TYPE_WOMEN_SINGLES:
            case Tournament::TYPE_WOMEN_DOUBLES:
                if ($player->gender !== 'female') {
                    throw new \InvalidArgumentException('Only female players can register for women\'s tournaments');
                }
                break;

            case Tournament::TYPE_MIXED_DOUBLES:
                // Mixed doubles will be validated at team level
                break;

            default:
                throw new \InvalidArgumentException('Invalid tournament type');
        }

        // Skill level validation (if tournament has level restrictions)
        if (isset($tournament->settings['min_skill_level'])) {
            if ($player->skill_rating < $tournament->settings['min_skill_level']) {
                throw new \InvalidArgumentException('Player skill level is below tournament minimum');
            }
        }

        if (isset($tournament->settings['max_skill_level'])) {
            if ($player->skill_rating > $tournament->settings['max_skill_level']) {
                throw new \InvalidArgumentException('Player skill level is above tournament maximum');
            }
        }

        // Age validation (if tournament has age restrictions)
        if (isset($tournament->settings['min_age'])) {
            $age = $player->date_of_birth ? now()->diffInYears($player->date_of_birth) : null;
            if ($age && $age < $tournament->settings['min_age']) {
                throw new \InvalidArgumentException('Player is below tournament minimum age');
            }
        }

        if (isset($tournament->settings['max_age'])) {
            $age = $player->date_of_birth ? now()->diffInYears($player->date_of_birth) : null;
            if ($age && $age > $tournament->settings['max_age']) {
                throw new \InvalidArgumentException('Player is above tournament maximum age');
            }
        }
    }
} 