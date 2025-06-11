<?php

namespace App\Domain\Match\Models;

use App\Domain\Tournament\Models\Tournament;
use App\Domain\Tournament\Models\TournamentParticipant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TournamentMatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'tournament_id',
        'participant1_id',
        'participant2_id',
        'match_number',
        'round_number',
        'round_name',
        'scheduled_at',
        'started_at',
        'completed_at',
        'court_number',
        'venue',
        'status',
        'winner_id',
        'loser_id',
        'score_data',
        'final_score',
        'sets_won_participant1',
        'sets_won_participant2',
        'games_won_participant1',
        'games_won_participant2',
        'match_format',
        'duration_minutes',
        'statistics',
        'referee_name',
        'umpire_name',
        'officials',
        'notes',
        'match_events',
        'incidents',
        'is_featured_match',
        'stream_url',
        'media_links',
        'next_match_id',
        'next_match_position',
        'weather_conditions',
        'special_conditions',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'score_data' => 'array',
        'sets_won_participant1' => 'integer',
        'sets_won_participant2' => 'integer',
        'games_won_participant1' => 'integer',
        'games_won_participant2' => 'integer',
        'duration_minutes' => 'integer',
        'statistics' => 'array',
        'officials' => 'array',
        'match_events' => 'array',
        'incidents' => 'array',
        'is_featured_match' => 'boolean',
        'media_links' => 'array',
        'weather_conditions' => 'array',
    ];

    protected $attributes = [
        'status' => 'scheduled',
        'match_format' => 'best_of_3',
        'sets_won_participant1' => 0,
        'sets_won_participant2' => 0,
        'games_won_participant1' => 0,
        'games_won_participant2' => 0,
        'is_featured_match' => false,
    ];

    // Match Status Constants
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_READY_TO_START = 'ready_to_start';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_POSTPONED = 'postponed';
    public const STATUS_WALKOVER = 'walkover';
    public const STATUS_NO_SHOW = 'no_show';

    // Match Format Constants
    public const FORMAT_BEST_OF_1 = 'best_of_1';
    public const FORMAT_BEST_OF_3 = 'best_of_3';
    public const FORMAT_BEST_OF_5 = 'best_of_5';

    // Next Match Position Constants
    public const NEXT_MATCH_PARTICIPANT1 = 'participant1';
    public const NEXT_MATCH_PARTICIPANT2 = 'participant2';

    /**
     * Relationships
     */
    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function participant1(): BelongsTo
    {
        return $this->belongsTo(TournamentParticipant::class, 'participant1_id');
    }

    public function participant2(): BelongsTo
    {
        return $this->belongsTo(TournamentParticipant::class, 'participant2_id');
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(TournamentParticipant::class, 'winner_id');
    }

    public function loser(): BelongsTo
    {
        return $this->belongsTo(TournamentParticipant::class, 'loser_id');
    }

    public function nextMatch(): BelongsTo
    {
        return $this->belongsTo(TournamentMatch::class, 'next_match_id');
    }

    /**
     * Scopes
     */
    public function scopeByTournament($query, int $tournamentId)
    {
        return $query->where('tournament_id', $tournamentId);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByRound($query, int $roundNumber)
    {
        return $query->where('round_number', $roundNumber);
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', self::STATUS_SCHEDULED);
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured_match', true);
    }

    public function scopeByCourt($query, string $courtNumber)
    {
        return $query->where('court_number', $courtNumber);
    }

    /**
     * Accessors & Mutators
     */
    public function getParticipantsAttribute()
    {
        return collect([$this->participant1, $this->participant2])->filter();
    }

    public function getIsCompletedAttribute(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function getIsInProgressAttribute(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    public function getHasWinnerAttribute(): bool
    {
        return !is_null($this->winner_id);
    }

    public function getDurationHoursAttribute(): ?float
    {
        return $this->duration_minutes ? $this->duration_minutes / 60 : null;
    }

    public function getMatchTitleAttribute(): string
    {
        $participant1Name = $this->participant1?->participant_name ?? 'TBD';
        $participant2Name = $this->participant2?->participant_name ?? 'TBD';
        
        return "{$participant1Name} vs {$participant2Name}";
    }

    public function getScoreDisplayAttribute(): string
    {
        return $this->final_score ?: 'Not started';
    }

    /**
     * Business Logic Methods  
     */
    public function canStart(): bool
    {
        return $this->status === self::STATUS_READY_TO_START 
            && $this->participant1 && $this->participant2;
    }

    public function start(): bool
    {
        if (!$this->canStart()) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_IN_PROGRESS,
            'started_at' => now(),
        ]);

        $this->addMatchEvent('match_started', 'Match started');
        return true;
    }

    public function complete(array $scoreData, ?int $winnerId = null): bool
    {
        if ($this->status !== self::STATUS_IN_PROGRESS) {
            return false;
        }

        // Calculate winner if not provided
        if (!$winnerId) {
            $winnerId = $this->calculateWinner($scoreData);
        }

        $loserId = $winnerId === $this->participant1_id ? $this->participant2_id : $this->participant1_id;

        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
            'winner_id' => $winnerId,
            'loser_id' => $loserId,
            'score_data' => $scoreData,
            'final_score' => $this->generateFinalScoreString($scoreData),
            'duration_minutes' => $this->started_at ? now()->diffInMinutes($this->started_at) : null,
        ]);

        // Update participant statistics
        $this->updateParticipantStats();
        
        // Progress winner to next match if exists
        $this->progressWinnerToNextMatch();

        $this->addMatchEvent('match_completed', 'Match completed');
        return true;
    }

    public function walkover(int $winnerId, string $reason = 'Walkover'): bool
    {
        if (!in_array($this->status, [self::STATUS_SCHEDULED, self::STATUS_READY_TO_START])) {
            return false;
        }

        $loserId = $winnerId === $this->participant1_id ? $this->participant2_id : $this->participant1_id;

        $this->update([
            'status' => self::STATUS_WALKOVER,
            'winner_id' => $winnerId,
            'loser_id' => $loserId,
            'final_score' => $reason,
            'completed_at' => now(),
            'notes' => $reason,
        ]);

        $this->progressWinnerToNextMatch();
        $this->addMatchEvent('walkover', $reason);
        return true;
    }

    public function postpone(string $reason = 'Match postponed'): bool
    {
        if (!in_array($this->status, [self::STATUS_SCHEDULED, self::STATUS_READY_TO_START])) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_POSTPONED,
            'notes' => $reason,
        ]);

        $this->addMatchEvent('postponed', $reason);
        return true;
    }

    public function cancel(string $reason = 'Match cancelled'): bool
    {
        if ($this->status === self::STATUS_COMPLETED) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_CANCELLED,
            'notes' => $reason,
        ]);

        $this->addMatchEvent('cancelled', $reason);
        return true;
    }

    public function reschedule(\DateTime $newDateTime, ?string $newCourt = null): bool
    {
        if ($this->status === self::STATUS_COMPLETED) {
            return false;
        }

        $updateData = ['scheduled_at' => $newDateTime];
        if ($newCourt) {
            $updateData['court_number'] = $newCourt;
        }

        $this->update($updateData);
        $this->addMatchEvent('rescheduled', "Match rescheduled to {$newDateTime->format('Y-m-d H:i')}");
        return true;
    }

    protected function calculateWinner(array $scoreData): ?int
    {
        // Simple implementation - can be extended based on scoring system
        $sets1 = $scoreData['sets_won_participant1'] ?? 0;
        $sets2 = $scoreData['sets_won_participant2'] ?? 0;

        if ($sets1 > $sets2) {
            return $this->participant1_id;
        } elseif ($sets2 > $sets1) {
            return $this->participant2_id;
        }

        return null; // Draw or error
    }

    protected function generateFinalScoreString(array $scoreData): string
    {
        // Generate human-readable score string
        $sets = $scoreData['sets'] ?? [];
        $scoreStrings = [];

        foreach ($sets as $set) {
            $scoreStrings[] = "{$set['participant1_games']}-{$set['participant2_games']}";
        }

        return implode(', ', $scoreStrings);
    }

    protected function updateParticipantStats(): void
    {
        if (!$this->winner_id || !$this->loser_id) return;

        // Update winner stats
        $this->winner->updateMatchStats(1, 0, $this->sets_won_participant1, $this->sets_won_participant2, 
                                       $this->games_won_participant1, $this->games_won_participant2);

        // Update loser stats  
        $this->loser->updateMatchStats(0, 1, $this->sets_won_participant2, $this->sets_won_participant1,
                                      $this->games_won_participant2, $this->games_won_participant1);
    }

    protected function progressWinnerToNextMatch(): void
    {
        if ($this->next_match_id && $this->winner_id) {
            $nextMatch = $this->nextMatch;
            if ($nextMatch) {
                $field = $this->next_match_position === self::NEXT_MATCH_PARTICIPANT1 ? 'participant1_id' : 'participant2_id';
                $nextMatch->update([$field => $this->winner_id]);
            }
        }
    }

    public function addMatchEvent(string $type, string $description, array $additionalData = []): void
    {
        $events = $this->match_events ?? [];
        $events[] = [
            'type' => $type,
            'description' => $description,
            'timestamp' => now()->toISOString(),
            'data' => $additionalData,
        ];

        $this->update(['match_events' => $events]);
    }

    /**
     * Static helper methods
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_SCHEDULED => 'Scheduled',
            self::STATUS_READY_TO_START => 'Ready to Start',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_POSTPONED => 'Postponed',
            self::STATUS_WALKOVER => 'Walkover',
            self::STATUS_NO_SHOW => 'No Show',
        ];
    }

    public static function getFormats(): array
    {
        return [
            self::FORMAT_BEST_OF_1 => 'Best of 1 Set',
            self::FORMAT_BEST_OF_3 => 'Best of 3 Sets',
            self::FORMAT_BEST_OF_5 => 'Best of 5 Sets',
        ];
    }
} 