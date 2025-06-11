<?php

namespace App\Http\Resources\Tournament;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TournamentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'slug' => $this->slug,
            'type' => $this->type,
            'format' => $this->format,
            'status' => $this->status,
            
            // Participant information
            'participants' => [
                'min' => $this->min_participants,
                'max' => $this->max_participants,
                'current' => $this->participants_count ?? $this->participants->count(),
                'available_slots' => $this->availableSlots,
                'is_full' => $this->isFull,
                'registration_progress' => $this->registrationProgress,
            ],
            
            // Dates
            'dates' => [
                'registration_start' => $this->registration_start_date?->toISOString(),
                'registration_end' => $this->registration_end_date?->toISOString(),
                'tournament_start' => $this->tournament_start_date?->toISOString(),
                'tournament_end' => $this->tournament_end_date?->toISOString(),
                'created_at' => $this->created_at->toISOString(),
                'updated_at' => $this->updated_at->toISOString(),
            ],
            
            // Registration status
            'registration' => [
                'is_open' => $this->isRegistrationOpen,
                'can_register' => $this->canRegister(),
                'entry_fee' => $this->entry_fee ? [
                    'amount' => $this->entry_fee,
                    'currency' => 'USD', // TODO: Make configurable
                    'formatted' => '$' . number_format($this->entry_fee, 2),
                ] : null,
            ],
            
            // Tournament details
            'details' => [
                'venue' => $this->venue,
                'rules' => $this->rules,
                'is_featured' => $this->is_featured,
                'is_doubles' => $this->isDoubles,
                'settings' => $this->settings,
            ],
            
            // Prizes
            'prizes' => $this->when($this->prizes, function () {
                return collect($this->prizes)->map(function ($prize) {
                    return [
                        'position' => $prize['position'],
                        'amount' => $prize['amount'],
                        'description' => $prize['description'] ?? null,
                        'formatted' => '$' . number_format($prize['amount'], 2),
                    ];
                })->sortBy('position')->values();
            }),
            
            // SEO and metadata
            'seo' => $this->when($this->meta_title || $this->meta_description, [
                'title' => $this->meta_title,
                'description' => $this->meta_description,
                'keywords' => $this->meta_keywords,
            ]),
            
            // Relationships (only when loaded)
            'organizer' => $this->whenLoaded('organizer', function () {
                return [
                    'id' => $this->organizer->id,
                    'name' => $this->organizer->name,
                    'email' => $this->organizer->email,
                    'avatar' => $this->organizer->avatar_url ?? null,
                ];
            }),
            
            'participants' => $this->whenLoaded('participants', function () {
                return TournamentParticipantResource::collection($this->participants);
            }),
            
            'confirmed_participants' => $this->whenLoaded('confirmedParticipants', function () {
                return TournamentParticipantResource::collection($this->confirmedParticipants);
            }),
            
            'matches' => $this->whenLoaded('matches', function () {
                return $this->matches->map(function ($match) {
                    return [
                        'id' => $match->id,
                        'round' => $match->round,
                        'status' => $match->status,
                        'scheduled_at' => $match->scheduled_at?->toISOString(),
                        'player1_id' => $match->player1_id,
                        'player2_id' => $match->player2_id,
                        'winner_id' => $match->winner_id,
                        'score' => $match->score,
                    ];
                });
            }),
            
            // Statistics
            'statistics' => $this->when($request->has('include_stats'), [
                'total_matches' => $this->matches()->count(),
                'completed_matches' => $this->matches()->where('status', 'completed')->count(),
                'average_match_duration' => $this->getAverageMatchDuration(),
                'prize_pool_total' => $this->getTotalPrizePool(),
            ]),
            
            // URLs
            'urls' => [
                'self' => route('tournaments.show', $this->slug),
                'register' => route('tournaments.register', $this->slug),
                'matches' => route('tournaments.matches', $this->slug),
            ],
        ];
    }

    /**
     * Get additional data that should be returned with the resource array.
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'tournament_types' => [
                    'men_singles' => 'Men\'s Singles',
                    'women_singles' => 'Women\'s Singles', 
                    'men_doubles' => 'Men\'s Doubles',
                    'women_doubles' => 'Women\'s Doubles',
                    'mixed_doubles' => 'Mixed Doubles',
                ],
                'tournament_formats' => [
                    'single_elimination' => 'Single Elimination',
                    'double_elimination' => 'Double Elimination',
                    'round_robin' => 'Round Robin',
                    'swiss' => 'Swiss System',
                ],
                'tournament_statuses' => [
                    'draft' => 'Draft',
                    'registration_open' => 'Registration Open',
                    'registration_closed' => 'Registration Closed',
                    'in_progress' => 'In Progress',
                    'completed' => 'Completed',
                    'cancelled' => 'Cancelled',
                ],
            ],
        ];
    }

    /**
     * Get average match duration for this tournament.
     */
    private function getAverageMatchDuration(): ?int
    {
        $completedMatches = $this->matches()
            ->where('status', 'completed')
            ->whereNotNull('started_at')
            ->whereNotNull('ended_at')
            ->get();

        if ($completedMatches->isEmpty()) {
            return null;
        }

        $totalDuration = $completedMatches->sum(function ($match) {
            return $match->started_at->diffInMinutes($match->ended_at);
        });

        return round($totalDuration / $completedMatches->count());
    }

    /**
     * Get total prize pool for this tournament.
     */
    private function getTotalPrizePool(): float
    {
        if (!$this->prizes) {
            return 0;
        }

        return collect($this->prizes)->sum('amount');
    }
} 