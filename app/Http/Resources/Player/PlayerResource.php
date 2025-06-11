<?php

namespace App\Http\Resources\Player;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlayerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->when($this->shouldShowPrivateInfo($request), $this->email),
            'gender' => $this->gender,
            'date_of_birth' => $this->when($this->shouldShowPrivateInfo($request), $this->date_of_birth?->toDateString()),
            'age' => $this->date_of_birth ? now()->diffInYears($this->date_of_birth) : null,
            
            // Location
            'location' => [
                'country' => $this->country,
                'city' => $this->city,
                'timezone' => $this->timezone,
            ],
            
            // Skill information
            'skill' => [
                'rating' => $this->skill_rating,
                'level' => $this->skill_level,
                'level_name' => $this->getSkillLevelName(),
                'rating_history' => $this->when($this->shouldShowPrivateInfo($request), $this->skill_rating_history),
            ],
            
            // Tournament statistics
            'statistics' => [
                'tournaments' => [
                    'total' => $this->total_tournaments,
                    'won' => $this->tournaments_won,
                    'win_rate' => $this->total_tournaments > 0 
                        ? round(($this->tournaments_won / $this->total_tournaments) * 100, 1) 
                        : 0,
                ],
                'matches' => [
                    'total' => $this->total_matches,
                    'won' => $this->matches_won,
                    'lost' => $this->total_matches - $this->matches_won,
                    'win_rate' => $this->total_matches > 0 
                        ? round(($this->matches_won / $this->total_matches) * 100, 1) 
                        : 0,
                ],
                'performance' => [
                    'current_streak' => $this->current_win_streak,
                    'best_streak' => $this->best_win_streak,
                    'recent_form' => $this->getRecentForm(),
                ],
            ],
            
            // Profile information
            'profile' => [
                'avatar' => $this->avatar_url,
                'bio' => $this->bio,
                'playing_style' => $this->playing_style,
                'favorite_surface' => $this->favorite_surface,
                'years_playing' => $this->years_playing,
                'verification_status' => $this->verification_status,
                'is_verified' => $this->verification_status === 'verified',
            ],
            
            // Contact and preferences (private info)
            'contact' => $this->when($this->shouldShowPrivateInfo($request), [
                'phone' => $this->phone,
                'emergency_contact' => $this->emergency_contact,
            ]),
            
            'preferences' => $this->when($this->shouldShowPrivateInfo($request), [
                'preferred_match_times' => $this->preferred_match_times,
                'notification_settings' => $this->notification_settings,
                'privacy_settings' => $this->privacy_settings,
            ]),
            
            // Rankings and achievements
            'rankings' => [
                'current_ranking' => $this->current_ranking,
                'highest_ranking' => $this->highest_ranking,
                'ranking_points' => $this->ranking_points,
            ],
            
            'achievements' => $this->when($this->achievements, function () {
                return collect($this->achievements)->map(function ($achievement) {
                    return [
                        'title' => $achievement['title'],
                        'description' => $achievement['description'],
                        'date_earned' => $achievement['date_earned'],
                        'tournament_id' => $achievement['tournament_id'] ?? null,
                    ];
                });
            }),
            
            // Activity timestamps
            'activity' => [
                'last_active' => $this->last_active_at?->toISOString(),
                'last_match' => $this->last_match_date?->toISOString(),
                'member_since' => $this->created_at->toISOString(),
                'profile_updated' => $this->updated_at->toISOString(),
            ],
            
            // Relationships (only when loaded)
            'current_teams' => $this->whenLoaded('teams', function () {
                return $this->teams->where('status', 'active')->map(function ($team) {
                    return [
                        'id' => $team->id,
                        'name' => $team->name,
                        'partner_id' => $team->player1_id === $this->id ? $team->player2_id : $team->player1_id,
                        'partner_name' => $team->player1_id === $this->id ? $team->player2->name : $team->player1->name,
                        'team_rating' => $team->team_rating,
                        'partnership_duration' => $team->partnership_start_date 
                            ? now()->diffInDays($team->partnership_start_date) 
                            : 0,
                    ];
                });
            }),
            
            'recent_tournaments' => $this->whenLoaded('tournamentParticipants', function () {
                return $this->tournamentParticipants()
                    ->with('tournament')
                    ->latest()
                    ->limit(5)
                    ->get()
                    ->map(function ($participant) {
                        return [
                            'tournament_id' => $participant->tournament->id,
                            'tournament_name' => $participant->tournament->name,
                            'tournament_slug' => $participant->tournament->slug,
                            'status' => $participant->status,
                            'final_position' => $participant->final_position,
                            'prize_money' => $participant->prize_money,
                            'completed_at' => $participant->tournament->tournament_end_date?->toISOString(),
                        ];
                    });
            }),
        ];
    }

    /**
     * Determine if private information should be shown.
     */
    private function shouldShowPrivateInfo(Request $request): bool
    {
        $user = $request->user();
        
        if (!$user) {
            return false;
        }
        
        // Show to the player themselves
        if ($this->id === $user->id) {
            return true;
        }
        
        // Show to admin users
        if (method_exists($user, 'hasRole') && $user->hasRole('admin')) {
            return true;
        }
        
        return false;
    }

    /**
     * Get skill level name from rating.
     */
    private function getSkillLevelName(): string
    {
        $rating = $this->skill_rating;
        
        if ($rating < 1000) return 'Beginner';
        if ($rating < 1500) return 'Intermediate';
        if ($rating < 2000) return 'Advanced';
        if ($rating < 2500) return 'Expert';
        
        return 'Professional';
    }

    /**
     * Get recent form (last 10 matches).
     */
    private function getRecentForm(): array
    {
        // This would typically fetch from a matches table
        // For now, return a placeholder
        return [
            'last_10_matches' => 'WWLWWLWWWL', // W = Win, L = Loss
            'win_rate' => 70.0,
            'form_trend' => 'improving', // improving, declining, stable
        ];
    }

    /**
     * Get additional data that should be returned with the resource array.
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'skill_levels' => [
                    'beginner' => '0-999 rating',
                    'intermediate' => '1000-1499 rating',
                    'advanced' => '1500-1999 rating',
                    'expert' => '2000-2499 rating',
                    'professional' => '2500+ rating',
                ],
                'verification_statuses' => [
                    'unverified' => 'Not Verified',
                    'pending' => 'Verification Pending',
                    'verified' => 'Verified Player',
                    'declined' => 'Verification Declined',
                ],
            ],
        ];
    }
} 