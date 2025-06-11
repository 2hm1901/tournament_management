<?php

namespace Database\Factories;

use App\Domain\Player\Models\Player;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Player\Models\Player>
 */
class PlayerFactory extends Factory
{
    protected $model = Player::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'player_name' => $this->faker->name(),
            'date_of_birth' => $this->faker->dateTimeBetween('-50 years', '-16 years'),
            'gender' => $this->faker->randomElement([
                Player::GENDER_MALE,
                Player::GENDER_FEMALE,
                Player::GENDER_OTHER,
            ]),
            'phone' => $this->faker->phoneNumber(),
            'bio' => $this->faker->paragraph(),
            'avatar' => $this->faker->optional()->imageUrl(),
            'skill_rating' => $this->faker->numberBetween(800, 2500),
            'skill_level' => $this->faker->randomElement([
                Player::SKILL_BEGINNER,
                Player::SKILL_INTERMEDIATE,
                Player::SKILL_ADVANCED,
                Player::SKILL_EXPERT,
                Player::SKILL_PROFESSIONAL,
            ]),
            'total_matches' => $this->faker->numberBetween(0, 100),
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
            'tournaments_played' => $this->faker->numberBetween(0, 30),
            'tournaments_won' => function (array $attributes) {
                return $this->faker->numberBetween(0, (int) ($attributes['tournaments_played'] / 4));
            },
            'best_tournament_finish' => $this->faker->optional()->numberBetween(1, 10),
            'preferred_tournament_types' => $this->faker->optional()->randomElements([
                'singles',
                'doubles',
                'mixed_doubles',
                'local',
                'regional',
                'national',
            ], $this->faker->numberBetween(1, 3)),
            'available_for_tournaments' => $this->faker->boolean(80),
            'notes' => $this->faker->optional()->sentence(),
            'city' => $this->faker->city(),
            'country' => $this->faker->country(),
            'timezone' => $this->faker->timezone(),
            'is_verified' => $this->faker->boolean(60),
            'is_active' => $this->faker->boolean(90),
            'last_active_at' => $this->faker->optional()->dateTimeBetween('-1 month', 'now'),
            'emergency_contact_name' => $this->faker->name(),
            'emergency_contact_phone' => $this->faker->phoneNumber(),
        ];
    }

    /**
     * Indicate that the player is male.
     */
    public function male(): static
    {
        return $this->state(fn (array $attributes) => [
            'gender' => Player::GENDER_MALE,
        ]);
    }

    /**
     * Indicate that the player is female.
     */
    public function female(): static
    {
        return $this->state(fn (array $attributes) => [
            'gender' => Player::GENDER_FEMALE,
        ]);
    }

    /**
     * Indicate that the player is highly skilled.
     */
    public function expert(): static
    {
        return $this->state(fn (array $attributes) => [
            'skill_level' => Player::SKILL_EXPERT,
            'skill_rating' => $this->faker->numberBetween(2000, 2500),
            'tournaments_won' => $this->faker->numberBetween(5, 15),
            'best_tournament_finish' => $this->faker->numberBetween(1, 3),
        ]);
    }

    /**
     * Indicate that the player is a beginner.
     */
    public function beginner(): static
    {
        return $this->state(fn (array $attributes) => [
            'skill_level' => Player::SKILL_BEGINNER,
            'skill_rating' => $this->faker->numberBetween(800, 1200),
            'total_matches' => $this->faker->numberBetween(0, 10),
            'tournaments_played' => $this->faker->numberBetween(0, 3),
        ]);
    }

    /**
     * Indicate that the player is verified.
     */
    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_verified' => true,
        ]);
    }

    /**
     * Indicate that the player is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
            'available_for_tournaments' => true,
        ]);
    }
} 