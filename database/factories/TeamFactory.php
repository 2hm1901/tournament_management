<?php

namespace Database\Factories;

use App\Domain\Tournament\Models\Team;
use App\Domain\Player\Models\Player;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Tournament\Models\Team>
 */
class TeamFactory extends Factory
{
    protected $model = Team::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $teamName = $this->faker->words(2, true) . ' Team';
        
        return [
            'name' => $teamName,
            'slug' => Str::slug($teamName),
            'description' => $this->faker->paragraph(),
            'type' => $this->faker->randomElement([
                Team::TYPE_MEN_DOUBLES,
                Team::TYPE_WOMEN_DOUBLES,
                Team::TYPE_MIXED_DOUBLES,
            ]),
            'player1_id' => Player::factory(),
            'player2_id' => function (array $attributes) {
                // Ensure player2 is different from player1
                return Player::factory()->create()->id;
            },
            'captain_id' => function (array $attributes) {
                // Captain must be one of the players
                return $this->faker->randomElement([$attributes['player1_id'], $attributes['player2_id']]);
            },
            'total_matches' => $this->faker->numberBetween(0, 50),
            'wins' => function (array $attributes) {
                return $this->faker->numberBetween(0, $attributes['total_matches']);
            },
            'losses' => function (array $attributes) {
                return max(0, $attributes['total_matches'] - $attributes['wins']);
            },
            'draws' => 0,
            'win_rate' => function (array $attributes) {
                return $attributes['total_matches'] > 0 
                    ? round(($attributes['wins'] / $attributes['total_matches']) * 100, 2)
                    : 0.00;
            },
            'tournaments_played' => $this->faker->numberBetween(0, 20),
            'tournaments_won' => function (array $attributes) {
                return $this->faker->numberBetween(0, (int) ($attributes['tournaments_played'] / 3));
            },
            'best_finish' => $this->faker->optional()->numberBetween(1, 10),
            'team_rating' => $this->faker->numberBetween(800, 2500),
            'average_player_rating' => $this->faker->numberBetween(800, 2500),
            'is_active' => $this->faker->boolean(80), // 80% chance of being active
            'status' => $this->faker->randomElement([
                Team::STATUS_ACTIVE,
                Team::STATUS_INACTIVE,
                Team::STATUS_DISBANDED,
                Team::STATUS_SUSPENDED,
            ]),
            'partnership_start_date' => $this->faker->dateTimeBetween('-2 years', 'now'),
            'partnership_end_date' => $this->faker->optional(0.2)->dateTimeBetween('now', '+1 year'),
            'partnership_notes' => $this->faker->optional()->sentence(),
            'preferred_communication' => $this->faker->randomElement(['email', 'phone', 'messaging']),
            'communication_preferences' => $this->faker->optional()->randomElements([
                'email_notifications',
                'sms_alerts', 
                'in_app_messages',
                'tournament_updates',
            ], $this->faker->numberBetween(1, 3)),
            'accepting_tournaments' => $this->faker->boolean(70), // 70% accepting tournaments
            'preferred_tournament_types' => $this->faker->optional()->randomElements([
                'singles',
                'doubles',
                'mixed_doubles',
                'local',
                'regional',
                'national',
            ], $this->faker->numberBetween(1, 4)),
            'special_requirements' => $this->faker->optional()->sentence(),
        ];
    }

    /**
     * Indicate that the team is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
            'status' => Team::STATUS_ACTIVE,
            'accepting_tournaments' => true,
            'partnership_end_date' => null,
        ]);
    }

    /**
     * Indicate that the team is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'status' => Team::STATUS_INACTIVE,
            'accepting_tournaments' => false,
        ]);
    }

    /**
     * Indicate that the team is disbanded.
     */
    public function disbanded(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'status' => Team::STATUS_DISBANDED,
            'accepting_tournaments' => false,
            'partnership_end_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ]);
    }

    /**
     * Indicate that the team is highly rated.
     */
    public function highRated(): static
    {
        return $this->state(fn (array $attributes) => [
            'team_rating' => $this->faker->numberBetween(2000, 2500),
            'average_player_rating' => $this->faker->numberBetween(1800, 2300),
            'tournaments_won' => $this->faker->numberBetween(3, 10),
            'best_finish' => $this->faker->numberBetween(1, 3),
        ]);
    }

    /**
     * Indicate that the team has specific player genders for type validation.
     */
    public function menDoubles(): static
    {
        return $this->state(function (array $attributes) {
            $player1 = Player::factory()->create(['gender' => Player::GENDER_MALE]);
            $player2 = Player::factory()->create(['gender' => Player::GENDER_MALE]);
            
            return [
                'type' => Team::TYPE_MEN_DOUBLES,
                'player1_id' => $player1->id,
                'player2_id' => $player2->id,
                'captain_id' => $this->faker->randomElement([$player1->id, $player2->id]),
            ];
        });
    }

    /**
     * Indicate that the team has specific player genders for type validation.
     */
    public function womenDoubles(): static
    {
        return $this->state(function (array $attributes) {
            $player1 = Player::factory()->create(['gender' => Player::GENDER_FEMALE]);
            $player2 = Player::factory()->create(['gender' => Player::GENDER_FEMALE]);
            
            return [
                'type' => Team::TYPE_WOMEN_DOUBLES,
                'player1_id' => $player1->id,
                'player2_id' => $player2->id,
                'captain_id' => $this->faker->randomElement([$player1->id, $player2->id]),
            ];
        });
    }

    /**
     * Indicate that the team has specific player genders for type validation.
     */
    public function mixedDoubles(): static
    {
        return $this->state(function (array $attributes) {
            $player1 = Player::factory()->create(['gender' => Player::GENDER_MALE]);
            $player2 = Player::factory()->create(['gender' => Player::GENDER_FEMALE]);
            
            return [
                'type' => Team::TYPE_MIXED_DOUBLES,
                'player1_id' => $player1->id,
                'player2_id' => $player2->id,
                'captain_id' => $this->faker->randomElement([$player1->id, $player2->id]),
            ];
        });
    }

    /**
     * Indicate that the team has won tournaments.
     */
    public function withTournamentWins(): static
    {
        return $this->state(fn (array $attributes) => [
            'tournaments_played' => $this->faker->numberBetween(5, 15),
            'tournaments_won' => $this->faker->numberBetween(1, 5),
            'best_finish' => 1,
        ]);
    }

    /**
     * Create a team with specific players.
     */
    public function withPlayers(Player $player1, Player $player2): static
    {
        return $this->state(fn (array $attributes) => [
            'player1_id' => $player1->id,
            'player2_id' => $player2->id,
            'captain_id' => $this->faker->randomElement([$player1->id, $player2->id]),
        ]);
    }
} 