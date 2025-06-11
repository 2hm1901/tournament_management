<?php

namespace App\Domain\Player\Models;

use App\Domain\Tournament\Models\Tournament;
use App\Domain\Tournament\Models\TournamentParticipant;
use App\Domain\Tournament\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Database\Factories\PlayerFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Player extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return PlayerFactory::new();
    }

    protected $fillable = [
        'user_id',
        'player_name',
        'date_of_birth',
        'gender',
        'phone',
        'bio',
        'avatar',
        'skill_rating',
        'skill_level',
        'total_matches',
        'wins',
        'losses',
        'draws',
        'win_rate',
        'tournaments_played',
        'tournaments_won',
        'best_tournament_finish',
        'preferred_tournament_types',
        'available_for_tournaments',
        'notes',
        'city',
        'country',
        'timezone',
        'is_verified',
        'is_active',
        'last_active_at',
        'emergency_contact_name',
        'emergency_contact_phone',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'skill_rating' => 'integer',
        'total_matches' => 'integer',
        'wins' => 'integer',
        'losses' => 'integer',
        'draws' => 'integer',
        'win_rate' => 'decimal:2',
        'tournaments_played' => 'integer',
        'tournaments_won' => 'integer',
        'best_tournament_finish' => 'integer',
        'preferred_tournament_types' => 'array',
        'available_for_tournaments' => 'boolean',
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
        'last_active_at' => 'datetime',
    ];

    protected $attributes = [
        'skill_rating' => 1000,
        'skill_level' => 'beginner',
        'total_matches' => 0,
        'wins' => 0,
        'losses' => 0,
        'draws' => 0,
        'win_rate' => 0.00,
        'tournaments_played' => 0,
        'tournaments_won' => 0,
        'available_for_tournaments' => true,
        'is_verified' => false,
        'is_active' => true,
    ];

    // Skill Level Constants
    public const SKILL_BEGINNER = 'beginner';
    public const SKILL_INTERMEDIATE = 'intermediate';
    public const SKILL_ADVANCED = 'advanced';
    public const SKILL_EXPERT = 'expert';
    public const SKILL_PROFESSIONAL = 'professional';

    // Gender Constants
    public const GENDER_MALE = 'male';
    public const GENDER_FEMALE = 'female';
    public const GENDER_OTHER = 'other';

    /**
     * Relationships
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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

    public function teamsAsPlayer1(): HasMany
    {
        return $this->hasMany(Team::class, 'player1_id');
    }

    public function teamsAsPlayer2(): HasMany
    {
        return $this->hasMany(Team::class, 'player2_id');
    }

    public function captainedTeams(): HasMany
    {
        return $this->hasMany(Team::class, 'captain_id');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeAvailableForTournaments($query)
    {
        return $query->where('available_for_tournaments', true)
                    ->where('is_active', true);
    }

    public function scopeBySkillLevel($query, string $skillLevel)
    {
        return $query->where('skill_level', $skillLevel);
    }

    public function scopeBySkillRatingRange($query, int $minRating, int $maxRating)
    {
        return $query->whereBetween('skill_rating', [$minRating, $maxRating]);
    }

    public function scopeByGender($query, string $gender)
    {
        return $query->where('gender', $gender);
    }

    public function scopeByLocation($query, string $city = null, string $country = null)
    {
        $query = $query->newQuery();
        
        if ($city) {
            $query->where('city', 'like', "%{$city}%");
        }
        
        if ($country) {
            $query->where('country', 'like', "%{$country}%");
        }
        
        return $query;
    }

    /**
     * Accessors & Mutators
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->player_name ?: $this->user->name;
    }

    public function getAgeAttribute(): ?int
    {
        return $this->date_of_birth ? $this->date_of_birth->age : null;
    }

    public function getAllTeamsAttribute()
    {
        return $this->teamsAsPlayer1->merge($this->teamsAsPlayer2);
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
        return $this->tournaments_played >= 5;
    }

    public function getIsChampionAttribute(): bool
    {
        return $this->tournaments_won > 0;
    }

    public function getRankingDescriptionAttribute(): string
    {
        return match (true) {
            $this->skill_rating >= 2000 => 'Professional',
            $this->skill_rating >= 1800 => 'Expert',
            $this->skill_rating >= 1600 => 'Advanced',
            $this->skill_rating >= 1400 => 'Intermediate',
            $this->skill_rating >= 1200 => 'Improving',
            default => 'Beginner'
        };
    }

    /**
     * Business Logic Methods
     */
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
        $this->updateSkillLevel();
    }

    public function updateTournamentResult(bool $won, int $position): void
    {
        $this->increment('tournaments_played');
        
        if ($won) {
            $this->increment('tournaments_won');
        }

        // Update best finish
        if (is_null($this->best_tournament_finish) || $position < $this->best_tournament_finish) {
            $this->update(['best_tournament_finish' => $position]);
        }
    }

    public function updateSkillRating(int $change): void
    {
        $newRating = max(0, $this->skill_rating + $change);
        $this->update(['skill_rating' => $newRating]);
        $this->updateSkillLevel();
    }

    protected function updateWinRate(): void
    {
        if ($this->total_matches > 0) {
            $winRate = ($this->wins / $this->total_matches) * 100;
            $this->update(['win_rate' => $winRate]);
        }
    }

    protected function updateSkillLevel(): void
    {
        $skillLevel = match (true) {
            $this->skill_rating >= 2000 => self::SKILL_PROFESSIONAL,
            $this->skill_rating >= 1800 => self::SKILL_EXPERT,
            $this->skill_rating >= 1600 => self::SKILL_ADVANCED,
            $this->skill_rating >= 1400 => self::SKILL_INTERMEDIATE,
            default => self::SKILL_BEGINNER
        };

        if ($this->skill_level !== $skillLevel) {
            $this->update(['skill_level' => $skillLevel]);
        }
    }

    public function canJoinTournament(Tournament $tournament): bool
    {
        if (!$this->is_active || !$this->available_for_tournaments) {
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
            'player_id' => $this->id,
            'registered_at' => now(),
        ], $additionalData));
    }

    public function activate(): void
    {
        $this->update([
            'is_active' => true,
            'last_active_at' => now(),
        ]);
    }

    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    public function verify(): void
    {
        $this->update(['is_verified' => true]);
    }

    /**
     * Static helper methods
     */
    public static function getSkillLevels(): array
    {
        return [
            self::SKILL_BEGINNER => 'Beginner',
            self::SKILL_INTERMEDIATE => 'Intermediate',
            self::SKILL_ADVANCED => 'Advanced',
            self::SKILL_EXPERT => 'Expert',
            self::SKILL_PROFESSIONAL => 'Professional',
        ];
    }

    public static function getGenders(): array
    {
        return [
            self::GENDER_MALE => 'Male',
            self::GENDER_FEMALE => 'Female',
            self::GENDER_OTHER => 'Other',
        ];
    }

    public static function getSkillRatingRanges(): array
    {
        return [
            'beginner' => ['min' => 0, 'max' => 1399],
            'intermediate' => ['min' => 1400, 'max' => 1599],
            'advanced' => ['min' => 1600, 'max' => 1799],
            'expert' => ['min' => 1800, 'max' => 1999],
            'professional' => ['min' => 2000, 'max' => 9999],
        ];
    }
} 