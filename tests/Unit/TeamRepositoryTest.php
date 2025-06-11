<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Domain\Team\Repositories\TeamRepositoryInterface;
use App\Infrastructure\Repositories\EloquentTeamRepository;
use App\Domain\Tournament\Models\Team;
use App\Domain\Player\Models\Player;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TeamRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private TeamRepositoryInterface $teamRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->teamRepository = new EloquentTeamRepository();
    }

    public function test_can_create_team_with_auto_generated_name(): void
    {
        // Create test players
        $player1 = Player::factory()->create(['player_name' => 'John Smith']);
        $player2 = Player::factory()->create(['player_name' => 'Jane Doe']);

        $teamData = [
            'player1_id' => $player1->id,
            'player2_id' => $player2->id,
        ];

        $team = $this->teamRepository->create($teamData);

        $this->assertInstanceOf(Team::class, $team);
        $this->assertEquals('John & Jane', $team->name);
        $this->assertEquals(Team::STATUS_ACTIVE, $team->status);
        $this->assertNotNull($team->partnership_start_date);
        $this->assertGreaterThan(0, $team->team_rating);
    }

    public function test_can_find_team_by_players(): void
    {
        $player1 = Player::factory()->create();
        $player2 = Player::factory()->create();

        $team = Team::factory()->create([
            'player1_id' => $player1->id,
            'player2_id' => $player2->id,
        ]);

        // Test finding with correct order
        $foundTeam = $this->teamRepository->findByPlayers($player1->id, $player2->id);
        $this->assertEquals($team->id, $foundTeam->id);

        // Test finding with reversed order
        $foundTeam = $this->teamRepository->findByPlayers($player2->id, $player1->id);
        $this->assertEquals($team->id, $foundTeam->id);
    }

    public function test_can_get_teams_by_player(): void
    {
        $player = Player::factory()->create();
        $otherPlayer1 = Player::factory()->create();
        $otherPlayer2 = Player::factory()->create();

        $team1 = Team::factory()->create([
            'player1_id' => $player->id,
            'player2_id' => $otherPlayer1->id,
        ]);

        $team2 = Team::factory()->create([
            'player1_id' => $otherPlayer2->id,
            'player2_id' => $player->id,
        ]);

        // Create team without our player
        Team::factory()->create([
            'player1_id' => $otherPlayer1->id,
            'player2_id' => $otherPlayer2->id,
        ]);

        $playerTeams = $this->teamRepository->getByPlayer($player->id);

        $this->assertCount(2, $playerTeams);
        $this->assertTrue($playerTeams->contains('id', $team1->id));
        $this->assertTrue($playerTeams->contains('id', $team2->id));
    }

    public function test_can_get_active_teams_only(): void
    {
        $player1 = Player::factory()->create();
        $player2 = Player::factory()->create();
        $player3 = Player::factory()->create();

        $activeTeam = Team::factory()->create([
            'player1_id' => $player1->id,
            'player2_id' => $player2->id,
            'status' => Team::STATUS_ACTIVE,
        ]);

        Team::factory()->create([
            'player1_id' => $player1->id,
            'player2_id' => $player3->id,
            'status' => Team::STATUS_INACTIVE,
        ]);

        $activeTeams = $this->teamRepository->getActiveByPlayer($player1->id);

        $this->assertCount(1, $activeTeams);
        $this->assertEquals($activeTeam->id, $activeTeams->first()->id);
    }

    public function test_can_update_team_rating(): void
    {
        $team = Team::factory()->create([
            'team_rating' => 1000,
            'partnership_notes' => null // Clear any existing notes
        ]);

        $updatedTeam = $this->teamRepository->updateTeamRating($team, 1200, [
            'match_id' => 1,
            'result' => 'win',
        ]);

        $this->assertEquals(1200, $updatedTeam->team_rating);
        $this->assertNotEmpty($updatedTeam->partnership_notes);
        
        $ratingHistory = json_decode($updatedTeam->partnership_notes, true);
        $lastRatingChange = end($ratingHistory);
        $this->assertEquals(1000, $lastRatingChange['old_rating']);
        $this->assertEquals(1200, $lastRatingChange['new_rating']);
        $this->assertEquals(200, $lastRatingChange['change']);
    }

    public function test_can_update_match_statistics(): void
    {
        $team = Team::factory()->create([
            'total_matches' => 5,
            'wins' => 3,
        ]);

        // Test winning match
        $updatedTeam = $this->teamRepository->updateMatchStatistics($team, ['is_win' => true]);

        $this->assertEquals(6, $updatedTeam->total_matches);
        $this->assertEquals(4, $updatedTeam->wins);
    }

    public function test_can_get_team_statistics(): void
    {
        // Create test data
        Team::factory()->count(3)->create(['status' => Team::STATUS_ACTIVE]);
        Team::factory()->count(2)->create(['status' => Team::STATUS_INACTIVE]);
        Team::factory()->count(1)->create(['status' => Team::STATUS_DISBANDED]);

        $stats = $this->teamRepository->getStatistics();

        $this->assertEquals(6, $stats['total_teams']);
        $this->assertEquals(3, $stats['active_teams']);
        $this->assertEquals(2, $stats['inactive_teams']);
        $this->assertEquals(1, $stats['disbanded_teams']);
        $this->assertArrayHasKey('average_team_rating', $stats);
        $this->assertArrayHasKey('tournament_participation', $stats);
    }

    public function test_can_apply_filters(): void
    {
        // Use fresh database state
        $this->refreshDatabase();
        
        $activeTeam = Team::factory()->create([
            'status' => Team::STATUS_ACTIVE,
            'team_rating' => 1500,
            'tournaments_won' => 2,
        ]);

        $inactiveTeam = Team::factory()->create([
            'status' => Team::STATUS_INACTIVE,
            'team_rating' => 1000,
            'tournaments_won' => 0,
        ]);

        // Test status filter
        $activeTeams = $this->teamRepository->getAll(['status' => Team::STATUS_ACTIVE]);
        $this->assertCount(1, $activeTeams);
        $this->assertEquals($activeTeam->id, $activeTeams->first()->id);

        // Test rating filter
        $highRatedTeams = $this->teamRepository->getAll(['team_rating_min' => 1400]);
        $this->assertCount(1, $highRatedTeams);
        $this->assertEquals($activeTeam->id, $highRatedTeams->first()->id);

        // Test tournament winners filter
        $winningTeams = $this->teamRepository->getAll(['tournaments_won_min' => 1]);
        $this->assertCount(1, $winningTeams);
        $this->assertEquals($activeTeam->id, $winningTeams->first()->id);
    }
}
