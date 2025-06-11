<?php

namespace App\Domain\Tournament\Models;

use App\Domain\Player\Models\Player;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Tournament extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'slug',
        'type',
        'format',
        'status',
        'max_participants',
        'current_participants',
        'min_participants',
        'registration_start_date',
        'registration_end_date',
        'tournament_start_date',
        'tournament_end_date',
        'settings',
        'entry_fee',
        'rules',
        'venue',
        'prizes',
        'organizer_id',
        'meta_title',
        'meta_description',
        'bracket_data',
        'results',
    ];

    protected $casts = [
        'registration_start_date' => 'datetime',
        'registration_end_date' => 'datetime',
        'tournament_start_date' => 'datetime',
        'tournament_end_date' => 'datetime',
        'settings' => 'array',
        'entry_fee' => 'decimal:2',
        'bracket_data' => 'array',
        'results' => 'array',
    ];

    protected $attributes = [
        'status' => 'draft',
        'format' => 'single_elimination',
        'max_participants' => 32,
        'current_participants' => 0,
        'min_participants' => 4,
        'entry_fee' => 0.00,
    ];

    // Tournament Types
    public const TYPE_MEN_SINGLES = 'men_singles';
    public const TYPE_WOMEN_SINGLES = 'women_singles';
    public const TYPE_MEN_DOUBLES = 'men_doubles';
    public const TYPE_WOMEN_DOUBLES = 'women_doubles';
    public const TYPE_MIXED_DOUBLES = 'mixed_doubles';

    // Tournament Formats
    public const FORMAT_SINGLE_ELIMINATION = 'single_elimination';
    public const FORMAT_DOUBLE_ELIMINATION = 'double_elimination';
    public const FORMAT_ROUND_ROBIN = 'round_robin';
    public const FORMAT_SWISS = 'swiss';

    // Tournament Status
    public const STATUS_DRAFT = 'draft';
    public const STATUS_REGISTRATION_OPEN = 'registration_open';
    public const STATUS_REGISTRATION_CLOSED = 'registration_closed';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Relationships
     */
    public function organizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(\App\Domain\Tournament\Models\TournamentParticipant::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(\App\Domain\Match\Models\TournamentMatch::class);
    }

    public function confirmedParticipants(): HasMany
    {
        return $this->participants()->where('registration_status', 'confirmed');
    }

    public function activePlayers(): HasManyThrough
    {
        return $this->hasManyThrough(
            Player::class,
            \App\Domain\Tournament\Models\TournamentParticipant::class,
            'tournament_id',
            'id',
            'id',
            'player_id'
        )->whereNotNull('tournament_participants.player_id')
         ->where('tournament_participants.registration_status', 'confirmed');
    }

    /**
     * Scopes
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('tournament_start_date', '>', now())
                    ->whereIn('status', [self::STATUS_REGISTRATION_OPEN, self::STATUS_REGISTRATION_CLOSED]);
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Accessors & Mutators
     */
    public function setNameAttribute(string $value): void
    {
        $this->attributes['name'] = $value;
        $this->attributes['slug'] = Str::slug($value);
    }

    public function getIsRegistrationOpenAttribute(): bool
    {
        return $this->status === self::STATUS_REGISTRATION_OPEN
            && $this->registration_start_date <= now()
            && $this->registration_end_date >= now()
            && $this->current_participants < $this->max_participants;
    }

    public function getIsFullAttribute(): bool
    {
        return $this->current_participants >= $this->max_participants;
    }

    public function getAvailableSlotsAttribute(): int
    {
        return max(0, $this->max_participants - $this->current_participants);
    }

    public function getRegistrationProgressAttribute(): float
    {
        if ($this->max_participants === 0) return 0;
        return ($this->current_participants / $this->max_participants) * 100;
    }

    public function getIsDoublesAttribute(): bool
    {
        return in_array($this->type, [
            self::TYPE_MEN_DOUBLES,
            self::TYPE_WOMEN_DOUBLES,
            self::TYPE_MIXED_DOUBLES,
        ]);
    }

    /**
     * Business Logic Methods
     */
    public function canRegister(): bool
    {
        return $this->is_registration_open && !$this->is_full;
    }

    public function canStart(): bool
    {
        return $this->status === self::STATUS_REGISTRATION_CLOSED
            && $this->current_participants >= $this->min_participants;
    }

    public function openRegistration(): bool
    {
        if ($this->status !== self::STATUS_DRAFT) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_REGISTRATION_OPEN,
            'registration_start_date' => now(),
        ]);

        return true;
    }

    public function closeRegistration(): bool
    {
        if ($this->status !== self::STATUS_REGISTRATION_OPEN) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_REGISTRATION_CLOSED,
            'registration_end_date' => now(),
        ]);

        return true;
    }

    public function startTournament(): bool
    {
        if (!$this->canStart()) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_IN_PROGRESS,
            'tournament_start_date' => now(),
        ]);

        return true;
    }

    public function completeTournament(array $results = []): bool
    {
        if ($this->status !== self::STATUS_IN_PROGRESS) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_COMPLETED,
            'tournament_end_date' => now(),
            'results' => $results,
        ]);

        return true;
    }

    public function incrementParticipantCount(): void
    {
        $this->increment('current_participants');
    }

    public function decrementParticipantCount(): void
    {
        $this->decrement('current_participants');
    }

    /**
     * Route model binding
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Static helper methods
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_MEN_SINGLES => 'Men Singles',
            self::TYPE_WOMEN_SINGLES => 'Women Singles',
            self::TYPE_MEN_DOUBLES => 'Men Doubles',
            self::TYPE_WOMEN_DOUBLES => 'Women Doubles',
            self::TYPE_MIXED_DOUBLES => 'Mixed Doubles',
        ];
    }

    public static function getFormats(): array
    {
        return [
            self::FORMAT_SINGLE_ELIMINATION => 'Single Elimination',
            self::FORMAT_DOUBLE_ELIMINATION => 'Double Elimination',
            self::FORMAT_ROUND_ROBIN => 'Round Robin',
            self::FORMAT_SWISS => 'Swiss System',
        ];
    }

    public static function getStatuses(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_REGISTRATION_OPEN => 'Registration Open',
            self::STATUS_REGISTRATION_CLOSED => 'Registration Closed',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }
} 