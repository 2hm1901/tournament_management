<?php

namespace App\Http\Resources\Match;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TournamentMatchResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tournament_id' => $this->tournament_id,
            'round' => $this->round,
            'match_number' => $this->match_number,
            'status' => $this->status,
            
            // Match scheduling
            'schedule' => [
                'scheduled_at' => $this->scheduled_at?->toISOString(),
                'started_at' => $this->started_at?->toISOString(),
                'ended_at' => $this->ended_at?->toISOString(),
                'duration_minutes' => $this->getDurationInMinutes(),
                'court' => $this->court,
                'venue' => $this->venue,
            ],
            
            // Participants
            'participants' => [
                'player1' => $this->formatPlayer($this->player1),
                'player2' => $this->formatPlayer($this->player2),
                'team1' => $this->whenLoaded('team1', function () {
                    return $this->formatTeam($this->team1);
                }),
                'team2' => $this->whenLoaded('team2', function () {
                    return $this->formatTeam($this->team2);
                }),
            ],
            
            // Match result
            'result' => [
                'winner_id' => $this->winner_id,
                'winner_type' => $this->winner_id ? $this->getWinnerType() : null,
                'loser_id' => $this->getLoser()?->id,
                'is_walkover' => $this->is_walkover,
                'walkover_reason' => $this->walkover_reason,
                'score' => $this->score,
                'formatted_score' => $this->getFormattedScore(),
                'sets' => $this->getSetBreakdown(),
            ],
            
            // Match statistics (if available)
            'statistics' => $this->when($this->statistics, [
                'total_points' => $this->statistics['total_points'] ?? null,
                'duration' => $this->statistics['duration'] ?? null,
                'aces' => $this->statistics['aces'] ?? null,
                'double_faults' => $this->statistics['double_faults'] ?? null,
                'first_serve_percentage' => $this->statistics['first_serve_percentage'] ?? null,
                'break_points_saved' => $this->statistics['break_points_saved'] ?? null,
            ]),
            
            // Officials and broadcast
            'officials' => [
                'referee' => $this->referee,
                'line_judges' => $this->line_judges,
                'ball_kids' => $this->ball_kids,
            ],
            
            'broadcast' => $this->when($this->broadcast_url || $this->live_stream_url, [
                'is_live' => $this->is_live_broadcast,
                'broadcast_url' => $this->broadcast_url,
                'live_stream_url' => $this->live_stream_url,
                'commentary_language' => $this->commentary_language,
            ]),
            
            // Bracket information
            'bracket' => [
                'next_match_id' => $this->next_match_id,
                'previous_match_1_id' => $this->previous_match_1_id,
                'previous_match_2_id' => $this->previous_match_2_id,
                'bracket_position' => $this->bracket_position,
                'is_final' => $this->round === 'final',
                'is_semifinal' => $this->round === 'semifinal',
                'is_quarterfinal' => $this->round === 'quarterfinal',
            ],
            
            // Event timeline
            'timeline' => $this->when($this->event_timeline, function () {
                return collect($this->event_timeline)->map(function ($event) {
                    return [
                        'timestamp' => $event['timestamp'],
                        'type' => $event['type'],
                        'description' => $event['description'],
                        'player_id' => $event['player_id'] ?? null,
                        'score' => $event['score'] ?? null,
                    ];
                });
            }),
            
            // Timestamps
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            
            // Relationships
            'tournament' => $this->whenLoaded('tournament', function () {
                return [
                    'id' => $this->tournament->id,
                    'name' => $this->tournament->name,
                    'slug' => $this->tournament->slug,
                    'type' => $this->tournament->type,
                    'format' => $this->tournament->format,
                ];
            }),
        ];
    }

    /**
     * Format player information.
     */
    private function formatPlayer($player): ?array
    {
        if (!$player) {
            return null;
        }

        return [
            'id' => $player->id,
            'name' => $player->name,
            'skill_rating' => $player->skill_rating,
            'country' => $player->country,
            'avatar' => $player->avatar_url,
            'seed' => $this->getPlayerSeed($player->id),
        ];
    }

    /**
     * Format team information.
     */
    private function formatTeam($team): ?array
    {
        if (!$team) {
            return null;
        }

        return [
            'id' => $team->id,
            'name' => $team->name,
            'team_rating' => $team->team_rating,
            'players' => [
                'player1' => $this->formatPlayer($team->player1),
                'player2' => $this->formatPlayer($team->player2),
            ],
            'seed' => $this->getTeamSeed($team->id),
        ];
    }

    /**
     * Get match duration in minutes.
     */
    private function getDurationInMinutes(): ?int
    {
        if (!$this->started_at || !$this->ended_at) {
            return null;
        }

        return $this->started_at->diffInMinutes($this->ended_at);
    }

    /**
     * Get winner type (player or team).
     */
    private function getWinnerType(): string
    {
        return $this->tournament->isDoubles ? 'team' : 'player';
    }

    /**
     * Get loser player/team.
     */
    private function getLoser()
    {
        if (!$this->winner_id) {
            return null;
        }

        if ($this->tournament->isDoubles) {
            return $this->winner_id === $this->team1_id ? $this->team2 : $this->team1;
        } else {
            return $this->winner_id === $this->player1_id ? $this->player2 : $this->player1;
        }
    }

    /**
     * Get formatted score display.
     */
    private function getFormattedScore(): ?string
    {
        if (!$this->score) {
            return null;
        }

        // Format score for display
        // Example: "6-4, 6-2" or "6-4, 3-6, 6-3"
        if (is_array($this->score) && isset($this->score['sets'])) {
            $sets = collect($this->score['sets'])->map(function ($set) {
                return $set['player1_score'] . '-' . $set['player2_score'];
            });
            
            return $sets->implode(', ');
        }

        return (string) $this->score;
    }

    /**
     * Get set-by-set breakdown.
     */
    private function getSetBreakdown(): ?array
    {
        if (!$this->score || !is_array($this->score) || !isset($this->score['sets'])) {
            return null;
        }

        return collect($this->score['sets'])->map(function ($set, $index) {
            return [
                'set_number' => $index + 1,
                'player1_score' => $set['player1_score'],
                'player2_score' => $set['player2_score'],
                'tiebreak' => $set['tiebreak'] ?? null,
                'duration_minutes' => $set['duration_minutes'] ?? null,
            ];
        })->toArray();
    }

    /**
     * Get player seed in tournament.
     */
    private function getPlayerSeed(int $playerId): ?int
    {
        // This would typically be fetched from tournament_participants table
        // For now, return null
        return null;
    }

    /**
     * Get team seed in tournament.
     */
    private function getTeamSeed(int $teamId): ?int
    {
        // This would typically be fetched from tournament_participants table
        // For now, return null
        return null;
    }

    /**
     * Get additional data that should be returned with the resource array.
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'match_statuses' => [
                    'scheduled' => 'Scheduled',
                    'in_progress' => 'In Progress',
                    'completed' => 'Completed',
                    'walkover' => 'Walkover',
                    'postponed' => 'Postponed',
                    'cancelled' => 'Cancelled',
                ],
                'round_names' => [
                    'first_round' => 'First Round',
                    'second_round' => 'Second Round',
                    'third_round' => 'Third Round',
                    'round_16' => 'Round of 16',
                    'quarterfinal' => 'Quarterfinal',
                    'semifinal' => 'Semifinal',
                    'final' => 'Final',
                ],
            ],
        ];
    }
} 