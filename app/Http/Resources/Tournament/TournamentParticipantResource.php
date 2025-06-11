<?php

namespace App\Http\Resources\Tournament;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TournamentParticipantResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'registration_date' => $this->registration_date?->toISOString(),
            'seed' => $this->seed,
            'position' => $this->final_position,
            
            // Performance statistics
            'performance' => [
                'matches_played' => $this->matches_played,
                'matches_won' => $this->matches_won,
                'matches_lost' => $this->matches_lost,
                'sets_won' => $this->sets_won,
                'sets_lost' => $this->sets_lost,
                'points_won' => $this->points_won,
                'points_lost' => $this->points_lost,
                'win_rate' => $this->matches_played > 0 
                    ? round(($this->matches_won / $this->matches_played) * 100, 1) 
                    : 0,
            ],
            
            // Prize information
            'prize' => $this->when($this->prize_money, [
                'amount' => $this->prize_money,
                'currency' => 'USD',
                'formatted' => '$' . number_format($this->prize_money, 2),
            ]),
            
            // Payment information (only for organizers and the participant themselves)
            'payment' => $this->when(
                $this->shouldShowPaymentInfo($request),
                [
                    'status' => $this->payment_status,
                    'amount' => $this->payment_amount,
                    'method' => $this->payment_method,
                    'transaction_id' => $this->payment_transaction_id,
                    'paid_at' => $this->payment_date?->toISOString(),
                    'formatted_amount' => '$' . number_format($this->payment_amount, 2),
                ]
            ),
            
            // Additional info
            'notes' => $this->notes,
            'emergency_contact' => $this->when(
                $this->shouldShowEmergencyContact($request),
                $this->emergency_contact
            ),
            
            // Timestamps
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            
            // Relationships (only when loaded)
            'player' => $this->whenLoaded('player', function () {
                return [
                    'id' => $this->player->id,
                    'name' => $this->player->name,
                    'email' => $this->player->email,
                    'skill_rating' => $this->player->skill_rating,
                    'skill_level' => $this->player->skill_level,
                    'gender' => $this->player->gender,
                    'country' => $this->player->country,
                    'city' => $this->player->city,
                    'avatar' => $this->player->avatar_url,
                    'statistics' => [
                        'total_tournaments' => $this->player->total_tournaments,
                        'tournaments_won' => $this->player->tournaments_won,
                        'total_matches' => $this->player->total_matches,
                        'matches_won' => $this->player->matches_won,
                        'win_rate' => $this->player->total_matches > 0 
                            ? round(($this->player->matches_won / $this->player->total_matches) * 100, 1) 
                            : 0,
                    ],
                ];
            }),
            
            'team' => $this->whenLoaded('team', function () {
                return [
                    'id' => $this->team->id,
                    'name' => $this->team->name,
                    'player1_id' => $this->team->player1_id,
                    'player2_id' => $this->team->player2_id,
                    'team_rating' => $this->team->team_rating,
                    'partnership_start_date' => $this->team->partnership_start_date?->toDateString(),
                    'total_tournaments' => $this->team->total_tournaments,
                    'tournaments_won' => $this->team->tournaments_won,
                    'players' => $this->whenLoaded('team.player1', function () {
                        return [
                            'player1' => [
                                'id' => $this->team->player1->id,
                                'name' => $this->team->player1->name,
                                'skill_rating' => $this->team->player1->skill_rating,
                                'avatar' => $this->team->player1->avatar_url,
                            ],
                            'player2' => [
                                'id' => $this->team->player2->id,
                                'name' => $this->team->player2->name,
                                'skill_rating' => $this->team->player2->skill_rating,
                                'avatar' => $this->team->player2->avatar_url,
                            ],
                        ];
                    }),
                ];
            }),
            
            'tournament' => $this->whenLoaded('tournament', function () {
                return [
                    'id' => $this->tournament->id,
                    'name' => $this->tournament->name,
                    'slug' => $this->tournament->slug,
                    'type' => $this->tournament->type,
                    'format' => $this->tournament->format,
                    'status' => $this->tournament->status,
                ];
            }),
        ];
    }

    /**
     * Determine if payment information should be shown.
     */
    private function shouldShowPaymentInfo(Request $request): bool
    {
        $user = $request->user();
        
        if (!$user) {
            return false;
        }
        
        // Show to the participant themselves
        if ($this->player_id === $user->id) {
            return true;
        }
        
        // Show to tournament organizer
        if ($this->tournament && $this->tournament->organizer_id === $user->id) {
            return true;
        }
        
        // Show to admin users (if you have role system)
        if (method_exists($user, 'hasRole') && $user->hasRole('admin')) {
            return true;
        }
        
        return false;
    }

    /**
     * Determine if emergency contact should be shown.
     */
    private function shouldShowEmergencyContact(Request $request): bool
    {
        $user = $request->user();
        
        if (!$user) {
            return false;
        }
        
        // Show to tournament organizer
        if ($this->tournament && $this->tournament->organizer_id === $user->id) {
            return true;
        }
        
        // Show to admin users
        if (method_exists($user, 'hasRole') && $user->hasRole('admin')) {
            return true;
        }
        
        return false;
    }

    /**
     * Get additional data that should be returned with the resource array.
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'participant_statuses' => [
                    'pending' => 'Registration Pending',
                    'confirmed' => 'Confirmed',
                    'rejected' => 'Rejected',
                    'withdrawn' => 'Withdrawn',
                    'disqualified' => 'Disqualified',
                    'eliminated' => 'Eliminated',
                ],
                'payment_statuses' => [
                    'pending' => 'Payment Pending',
                    'paid' => 'Paid',
                    'refunded' => 'Refunded',
                    'failed' => 'Payment Failed',
                ],
            ],
        ];
    }
} 