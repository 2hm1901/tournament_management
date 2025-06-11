<?php

namespace App\Domain\Tournament\Models;

use App\Domain\Player\Models\Player;
use App\Domain\Tournament\Models\Team;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TournamentParticipant extends Model
{
    use HasFactory;

    protected $fillable = [
        'tournament_id',
        'player_id',
        'team_id',
        'registration_status',
        'registered_at',
        'confirmed_at',
        'seed_number',
        'current_round',
        'tournament_status',
        'matches_played',
        'matches_won',
        'matches_lost',
        'sets_won',
        'sets_lost',
        'games_won',
        'games_lost',
        'final_position',
        'prize_money',
        'entry_fee_paid',
        'payment_date',
        'payment_method',
        'payment_reference',
        'special_requirements',
        'emergency_contact',
        'notes',
    ];

    protected $casts = [
        'registered_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'payment_date' => 'datetime',
        'prize_money' => 'decimal:2',
        'entry_fee_paid' => 'boolean',
        'emergency_contact' => 'array',
    ];

    protected $attributes = [
        'registration_status' => 'pending',
        'current_round' => 0,
        'tournament_status' => 'active',
        'matches_played' => 0,
        'matches_won' => 0,
        'matches_lost' => 0,
        'sets_won' => 0,
        'sets_lost' => 0,
        'games_won' => 0,
        'games_lost' => 0,
        'prize_money' => 0.00,
        'entry_fee_paid' => false,
    ];

    // Registration Status Constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_WAITLISTED = 'waitlisted';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_WITHDRAWN = 'withdrawn';
    public const STATUS_DISQUALIFIED = 'disqualified';

    // Tournament Status Constants
    public const TOURNAMENT_STATUS_ACTIVE = 'active';
    public const TOURNAMENT_STATUS_ELIMINATED = 'eliminated';
    public const TOURNAMENT_STATUS_WITHDRAWN = 'withdrawn';
    public const TOURNAMENT_STATUS_BYE = 'bye';
    public const TOURNAMENT_STATUS_CHAMPION = 'champion';
    public const TOURNAMENT_STATUS_FINALIST = 'finalist';
    public const TOURNAMENT_STATUS_SEMIFINALIST = 'semifinalist';

    /**
     * Relationships
     */
    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Scopes
     */
    public function scopeConfirmed($query)
    {
        return $query->where('registration_status', self::STATUS_CONFIRMED);
    }

    public function scopePending($query)
    {
        return $query->where('registration_status', self::STATUS_PENDING);
    }

    public function scopeActive($query)
    {
        return $query->where('tournament_status', self::TOURNAMENT_STATUS_ACTIVE);
    }

    public function scopeEliminated($query)
    {
        return $query->where('tournament_status', self::TOURNAMENT_STATUS_ELIMINATED);
    }

    public function scopeByTournament($query, int $tournamentId)
    {
        return $query->where('tournament_id', $tournamentId);
    }

    /**
     * Accessors & Mutators
     */
    public function getWinRateAttribute(): float
    {
        if ($this->matches_played === 0) return 0;
        return ($this->matches_won / $this->matches_played) * 100;
    }

    public function getIsPlayerParticipantAttribute(): bool
    {
        return !is_null($this->player_id);
    }

    public function getIsTeamParticipantAttribute(): bool
    {
        return !is_null($this->team_id);
    }

    public function getParticipantNameAttribute(): string
    {
        if ($this->is_player_participant) {
            return $this->player->player_name ?? $this->player->user->name;
        }
        
        if ($this->is_team_participant) {
            return $this->team->name;
        }

        return 'Unknown Participant';
    }

    /**
     * Business Logic Methods
     */
    public function confirm(): bool
    {
        if ($this->registration_status !== self::STATUS_PENDING) {
            return false;
        }

        $this->update([
            'registration_status' => self::STATUS_CONFIRMED,
            'confirmed_at' => now(),
        ]);

        // Increment tournament participant count
        $this->tournament->incrementParticipantCount();

        return true;
    }

    public function reject(): bool
    {
        if (!in_array($this->registration_status, [self::STATUS_PENDING, self::STATUS_WAITLISTED])) {
            return false;
        }

        $this->update(['registration_status' => self::STATUS_REJECTED]);
        return true;
    }

    public function withdraw(): bool
    {
        if (!in_array($this->registration_status, [self::STATUS_CONFIRMED, self::STATUS_PENDING])) {
            return false;
        }

        $oldStatus = $this->registration_status;
        $this->update([
            'registration_status' => self::STATUS_WITHDRAWN,
            'tournament_status' => self::TOURNAMENT_STATUS_WITHDRAWN,
        ]);

        // Decrement tournament participant count if was confirmed
        if ($oldStatus === self::STATUS_CONFIRMED) {
            $this->tournament->decrementParticipantCount();
        }

        return true;
    }

    public function disqualify(): bool
    {
        $this->update([
            'registration_status' => self::STATUS_DISQUALIFIED,
            'tournament_status' => self::TOURNAMENT_STATUS_ELIMINATED,
        ]);

        return true;
    }

    public function eliminate(): bool
    {
        if ($this->tournament_status !== self::TOURNAMENT_STATUS_ACTIVE) {
            return false;
        }

        $this->update(['tournament_status' => self::TOURNAMENT_STATUS_ELIMINATED]);
        return true;
    }

    public function updateMatchStats(int $won, int $lost, int $setsWon, int $setsLost, int $gamesWon, int $gamesLost): void
    {
        $this->increment('matches_played');
        $this->increment('matches_won', $won);
        $this->increment('matches_lost', $lost);
        $this->increment('sets_won', $setsWon);
        $this->increment('sets_lost', $setsLost);
        $this->increment('games_won', $gamesWon);
        $this->increment('games_lost', $gamesLost);
    }

    public function setAsChampion(int $position = 1, float $prizeMoney = 0): void
    {
        $status = match ($position) {
            1 => self::TOURNAMENT_STATUS_CHAMPION,
            2 => self::TOURNAMENT_STATUS_FINALIST,
            3, 4 => self::TOURNAMENT_STATUS_SEMIFINALIST,
            default => self::TOURNAMENT_STATUS_ELIMINATED,
        };

        $this->update([
            'tournament_status' => $status,
            'final_position' => $position,
            'prize_money' => $prizeMoney,
        ]);
    }

    /**
     * Static helper methods
     */
    public static function getRegistrationStatuses(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_CONFIRMED => 'Confirmed',
            self::STATUS_WAITLISTED => 'Waitlisted',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_WITHDRAWN => 'Withdrawn',
            self::STATUS_DISQUALIFIED => 'Disqualified',
        ];
    }

    public static function getTournamentStatuses(): array
    {
        return [
            self::TOURNAMENT_STATUS_ACTIVE => 'Active',
            self::TOURNAMENT_STATUS_ELIMINATED => 'Eliminated',
            self::TOURNAMENT_STATUS_WITHDRAWN => 'Withdrawn',
            self::TOURNAMENT_STATUS_BYE => 'Bye',
            self::TOURNAMENT_STATUS_CHAMPION => 'Champion',
            self::TOURNAMENT_STATUS_FINALIST => 'Finalist',
            self::TOURNAMENT_STATUS_SEMIFINALIST => 'Semifinalist',
        ];
    }
} 