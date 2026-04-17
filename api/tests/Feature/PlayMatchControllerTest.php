<?php

namespace Tests\Feature;

use App\Enums\FixtureStatus;
use App\Models\Fixture;
use App\Models\Group;
use App\Models\MatchEvent;
use App\Models\Team;
use App\Models\TeamStat;
use App\Models\Tournament;
use App\Services\LeagueTableService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\TestCase;

class PlayMatchControllerTest extends TestCase
{
    use RefreshDatabase;

    private function makeFixture(string $status = 'scheduled'): Fixture
    {
        $tournament = Tournament::create(['name' => 'Test Tournament']);
        $group      = Group::create(['tournament_id' => $tournament->id, 'name' => 'A']);

        $home = $this->makeTeam('Home FC');
        $away = $this->makeTeam('Away FC');

        $group->teams()->attach([$home->id, $away->id]);

        return Fixture::create([
            'id'           => (string) Str::uuid(),
            'group_id'     => $group->id,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'match_week'   => 1,
            'status'       => $status,
        ]);
    }

    private function makeTeam(string $name): Team
    {
        $team = Team::create(['name' => $name, 'country_code' => 'ENG']);
        TeamStat::create(array_merge($this->defaultStats(), ['team_id' => $team->id]));
        return $team;
    }

    private function defaultStats(): array
    {
        return [
            'attack' => 82, 'midfield' => 80, 'defense' => 80, 'speed' => 79,
            'pass' => 80, 'shot' => 80, 'goalkeeper' => 80, 'finishing' => 81,
            'chance_creation' => 80, 'pressing' => 79, 'set_piece_strength' => 78,
            'winner_mentality' => 8, 'loser_mentality' => 7, 'consistency' => 80,
            'discipline' => 80, 'fatigue_resistance' => 80, 'big_match_performance' => 80,
            'resilience' => 80, 'manager_influence' => 8, 'squad_depth' => 25,
            'injury_risk' => 5, 'star_players_count' => 3, 'home_advantage' => 7,
            'min_temp_performance' => 0, 'max_temp_performance' => 30, 'pot' => 1,
        ];
    }

    public function test_pending_match_can_be_simulated(): void
    {
        $fixture = $this->makeFixture();

        $response = $this->postJson("/api/fixtures/{$fixture->id}/play");

        $response->assertStatus(200)
                 ->assertJson(['success' => true])
                 ->assertJsonStructure([
                     'success',
                     'data' => ['fixture_id', 'score', 'timeline', 'standings'],
                 ]);
    }

    public function test_already_played_match_cannot_be_simulated_again(): void
    {
        $fixture = $this->makeFixture('completed');

        $response = $this->postJson("/api/fixtures/{$fixture->id}/play");

        $response->assertStatus(422)
                 ->assertJson(['success' => false, 'code' => 'INVALID_STATE'])
                 ->assertJsonStructure(['success', 'message', 'code', 'errors']);
    }

    public function test_simulated_match_persists_final_score(): void
    {
        $fixture = $this->makeFixture();

        $this->postJson("/api/fixtures/{$fixture->id}/play");

        $fixture->refresh();
        $this->assertEquals(FixtureStatus::Completed, $fixture->status);
        $this->assertNotNull($fixture->home_score);
        $this->assertNotNull($fixture->away_score);
        $this->assertGreaterThanOrEqual(0, $fixture->home_score);
        $this->assertGreaterThanOrEqual(0, $fixture->away_score);
    }

    public function test_timeline_events_are_stored_in_database(): void
    {
        $fixture = $this->makeFixture();

        $this->postJson("/api/fixtures/{$fixture->id}/play");

        $this->assertGreaterThan(0, MatchEvent::where('fixture_id', $fixture->id)->count());
    }

    public function test_full_time_event_is_always_stored(): void
    {
        $fixture = $this->makeFixture();

        $this->postJson("/api/fixtures/{$fixture->id}/play");

        $this->assertDatabaseHas('match_events', [
            'fixture_id' => $fixture->id,
            'event_type' => 'full_time',
        ]);
    }


    public function test_standings_are_recalculated_after_match(): void
    {
        $fixture = $this->makeFixture();

        $response = $this->postJson("/api/fixtures/{$fixture->id}/play");

        $response->assertStatus(200);
        $response->assertJsonStructure(['data' => ['standings']]);
        $this->assertCount(2, $response->json('data.standings')); // home + away

        // Flush any cached standings that were computed against stale relation
        // data during the request lifecycle, then re-query the group so the
        // table reflects the freshly persisted fixture row.
        Cache::flush();
        $group     = Group::with(['teams', 'fixtures'])->findOrFail($fixture->group_id);
        $standings = app(LeagueTableService::class)->forGroup($group)->values();

        $this->assertCount(2, $standings);
        $this->assertEquals(1, $standings[0]->played);
        $this->assertEquals(1, $standings[1]->played);
    }

    public function test_score_is_never_negative(): void
    {
        $fixture = $this->makeFixture();

        $this->postJson("/api/fixtures/{$fixture->id}/play");

        $fixture->refresh();
        $this->assertGreaterThanOrEqual(0, $fixture->home_score);
        $this->assertGreaterThanOrEqual(0, $fixture->away_score);
    }
}
