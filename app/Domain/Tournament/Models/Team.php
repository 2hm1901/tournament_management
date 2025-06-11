<?php

namespace App\Domain\Tournament\Models;

use App\Domain\Player\Models\Player;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Database\Factories\TeamFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Team extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return TeamFactory::new();
    }

    protected $fillable = [
        'name',
        'slug',
        'description',
        'type',
        'player1_id',
        'player2_id',
        'captain_id',
        'total_matches',
        'wins',
        'losses',
        'draws',
        'win_rate',
        'tournaments_played',
        'tournaments_won',
        'best_finish',
        'team_rating',
        'average_player_rating',
        'is_active',
        'status',
        'partnership_start_date',
        'partnership_end_date',
        'partnership_notes',
        'preferred_communication',
        'communication_preferences',
        'accepting_tournaments',
        'preferred_tournament_types',
        'special_requirements',
    ];

    protected $casts = [
        'total_matches' => 'integer',
        'wins' => 'integer',
        'losses' => 'integer',
        'draws' => 'integer',
        'win_rate' => 'decimal:2',
        'tournaments_played' => 'integer',
        'tournaments_won' => 'integer',
        'best_finish' => 'integer',
        'team_rating' => 'integer',
        'average_player_rating' => 'integer',
        'is_active' => 'boolean',
        'partnership_start_date' => 'date',
        'partnership_end_date' => 'date',
        'communication_preferences' => 'array',
        'accepting_tournaments' => 'boolean',
        'preferred_tournament_types' => 'array',
    ];

    protected $attributes = [
        'total_matches' => 0,
        'wins' => 0,
        'losses' => 0,
        'draws' => 0,
        'win_rate' => 0.00,
        'tournaments_played' => 0,
        'tournaments_won' => 0,
        'team_rating' => 1000,
        'average_player_rating' => 1000,
        'is_active' => true,
        'status' => 'active',
        'accepting_tournaments' => true,
    ];

    // Team Type Constants
    public const TYPE_MEN_DOUBLES = 'men_doubles';
    public const TYPE_WOMEN_DOUBLES = 'women_doubles';
    public const TYPE_MIXED_DOUBLES = 'mixed_doubles';

    // Team Status Constants
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_DISBANDED = 'disbanded';
    public const STATUS_SUSPENDED = 'suspended';

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($team) {
            if (empty($team->partnership_start_date)) {
                $team->partnership_start_date = now()->toDateString();
            }
        });

        // Disable auto-calculation during testing
        if (!app()->runningUnitTests()) {
            static::created(function ($team) {
                $team->calculateTeamRatings();
            });
        }
    }

    /**
     * Relationships
     */
    public function player1(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'player1_id');
    }

    public function player2(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'player2_id');
    }

    public function captain(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'captain_id');
    }

    public function tournamentParticipations(): HasMany
    {
        return $this->hasMany(TournamentParticipant::class);
    }

    public function tournaments(): BelongsToMany
    {
        return $this->belongsToMany(Tournament::class, 'tournament_participants')
                    ->withPivot([
                        'registration_status',
                        'tournament_status',
                        'seed_number',
                        'final_position',
                        'matches_played',
                        'matches_won',
                        'matches_lost'
                    ])
                    ->withTimestamps();
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
                    ->where('is_active', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeAcceptingTournaments($query)
    {
        return $query->where('accepting_tournaments', true)
                    ->where('status', self::STATUS_ACTIVE)
                    ->where('is_active', true);
    }

    public function scopeByRatingRange($query, int $minRating, int $maxRating)
    {
        return $query->whereBetween('team_rating', [$minRating, $maxRating]);
    }

    /**
     * Accessors & Mutators
     */
    public function setNameAttribute(string $value): void
    {
        $this->attributes['name'] = $value;
        $this->attributes['slug'] = Str::slug($value);
    }

    public function getPlayersAttribute()
    {
        return collect([$this->player1, $this->player2])->filter();
    }

    public function getPartnershipDurationAttribute(): ?int
    {
        if (!$this->partnership_start_date) return null;
        
        $endDate = $this->partnership_end_date ?: now()->toDateString();
        return $this->partnership_start_date->diffInDays($endDate);
    }

    public function getWinPercentageAttribute(): float
    {
        return $this->win_rate;
    }

    public function getLossPercentageAttribute(): float
    {
        return $this->total_matches > 0 ? ($this->losses / $this->total_matches) * 100 : 0;
    }

    public function getIsExperiencedAttribute(): bool
    {
        return $this->tournaments_played >= 3;
    }

    public function getIsChampionshipTeamAttribute(): bool
    {
        return $this->tournaments_won > 0;
    }

    /**
     * Business Logic Methods
     */
    public function calculateTeamRatings(): void
    {
        if ($this->player1 && $this->player2) {
            $averagePlayerRating = ($this->player1->skill_rating + $this->player2->skill_rating) / 2;
            
            // Team rating could be slightly different from average (synergy factor)
            $teamRating = $averagePlayerRating;
            
            $this->update([
                'average_player_rating' => $averagePlayerRating,
                'team_rating' => $teamRating,
            ]);
        }
    }

    public function updateMatchResult(bool $won, bool $lost = false, bool $draw = false): void
    {
        $this->increment('total_matches');
        
        if ($won) {
            $this->increment('wins');
        } elseif ($lost) {
            $this->increment('losses');
        } elseif ($draw) {
            $this->increment('draws');
        }

        $this->updateWinRate();
    }

    public function updateTournamentResult(bool $won, int $position): void
    {
        $this->increment('tournaments_played');
        
        if ($won) {
            $this->increment('tournaments_won');
        }

        // Update best finish
        if (is_null($this->best_finish) || $position < $this->best_finish) {
            $this->update(['best_finish' => $position]);
        }
    }

    protected function updateWinRate(): void
    {
        if ($this->total_matches > 0) {
            $winRate = ($this->wins / $this->total_matches) * 100;
            $this->update(['win_rate' => $winRate]);
        }
    }

    public function canJoinTournament(Tournament $tournament): bool
    {
        if (!$this->is_active || !$this->accepting_tournaments || $this->status !== self::STATUS_ACTIVE) {
            return false;
        }

        // Check if tournament type matches team type
        $compatibleTypes = [
            self::TYPE_MEN_DOUBLES => [Tournament::TYPE_MEN_DOUBLES],
            self::TYPE_WOMEN_DOUBLES => [Tournament::TYPE_WOMEN_DOUBLES],
            self::TYPE_MIXED_DOUBLES => [Tournament::TYPE_MIXED_DOUBLES],
        ];

        if (!in_array($tournament->type, $compatibleTypes[$this->type] ?? [])) {
            return false;
        }

        // Check if already registered
        $existingParticipation = $this->tournamentParticipations()
            ->where('tournament_id', $tournament->id)
            ->whereIn('registration_status', ['pending', 'confirmed'])
            ->exists();

        if ($existingParticipation) {
            return false;
        }

        // Check tournament capacity
        return $tournament->canRegister();
    }

    public function joinTournament(Tournament $tournament, array $additionalData = []): ?TournamentParticipant
    {
        if (!$this->canJoinTournament($tournament)) {
            return null;
        }

        return $tournament->participants()->create(array_merge([
            'team_id' => $this->id,
            'registered_at' => now(),
        ], $additionalData));
    }

    public function activate(): void
    {
        $this->update([
            'is_active' => true,
            'status' => self::STATUS_ACTIVE,
        ]);
    }

    public function deactivate(): void
    {
        $this->update([
            'is_active' => false,
            'status' => self::STATUS_INACTIVE,
        ]);
    }

    public function disband(): void
    {
        $this->update([
            'is_active' => false,
            'status' => self::STATUS_DISBANDED,
            'partnership_end_date' => now()->toDateString(),
            'accepting_tournaments' => false,
        ]);
    }

    public function suspend(string $reason = null): void
    {
        $this->update([
            'status' => self::STATUS_SUSPENDED,
            'accepting_tournaments' => false,
            'partnership_notes' => $reason ? "Suspended: {$reason}" : 'Suspended',
        ]);
    }

    public function validatePartnership(): bool
    {
        // Check if players are different
        if ($this->player1_id === $this->player2_id) {
            return false;
        }

        // Check if captain is one of the players
        if (!in_array($this->captain_id, [$this->player1_id, $this->player2_id])) {
            return false;
        }

        // Check team type compatibility with player genders
        if ($this->type === self::TYPE_MEN_DOUBLES) {
            return $this->player1->gender === Player::GENDER_MALE && 
                   $this->player2->gender === Player::GENDER_MALE;
        }

        if ($this->type === self::TYPE_WOMEN_DOUBLES) {
            return $this->player1->gender === Player::GENDER_FEMALE && 
                   $this->player2->gender === Player::GENDER_FEMALE;
        }

        if ($this->type === self::TYPE_MIXED_DOUBLES) {
            return ($this->player1->gender === Player::GENDER_MALE && $this->player2->gender === Player::GENDER_FEMALE) ||
                   ($this->player1->gender === Player::GENDER_FEMALE && $this->player2->gender === Player::GENDER_MALE);
        }

        return true;
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
            self::TYPE_MEN_DOUBLES => 'Men Doubles',
            self::TYPE_WOMEN_DOUBLES => 'Women Doubles',
            self::TYPE_MIXED_DOUBLES => 'Mixed Doubles',
        ];
    }

    public static function getStatuses(): array
    {
        return [
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_INACTIVE => 'Inactive',
            self::STATUS_DISBANDED => 'Disbanded',
            self::STATUS_SUSPENDED => 'Suspended',
        ];
    }
} 