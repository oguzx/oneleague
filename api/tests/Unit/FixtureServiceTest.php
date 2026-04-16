<?php

namespace Tests\Unit;

use App\Models\Group;
use App\Models\Team;
use App\Services\FixtureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FixtureServiceTest extends TestCase
{
    use RefreshDatabase;

    private FixtureService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new FixtureService();
    }

    private function makeTeams(int $count): array
    {
        return array_map(fn($i) => Team::create([
            'name'         => "Team {$i}",
            'country_code' => 'ENG',
        ]), range(1, $count));
    }

    public function test_produces_12_fixtures_for_4_team_group(): void
    {
        $teams = $this->makeTeams(4);
        $schedule = $this->service->buildSchedule($teams);

        $this->assertCount(12, $schedule);
    }

    public function test_produces_exactly_6_match_weeks(): void
    {
        $teams = $this->makeTeams(4);
        $schedule = $this->service->buildSchedule($teams);

        $weeks = $schedule->pluck('week')->unique()->sort()->values()->all();
        $this->assertEquals([1, 2, 3, 4, 5, 6], $weeks);
    }

    public function test_each_team_plays_exactly_once_per_week(): void
    {
        $teams = $this->makeTeams(4);
        $schedule = $this->service->buildSchedule($teams);

        foreach ($schedule->groupBy('week') as $week => $fixtures) {
            $participantIds = $fixtures->flatMap(fn($f) => [$f['home']->id, $f['away']->id]);
            $this->assertCount(4, $participantIds, "Week {$week} should have 4 participants");
            $this->assertCount(4, $participantIds->unique(), "Week {$week}: no team plays twice");
        }
    }

    public function test_each_pair_plays_exactly_twice(): void
    {
        $teams = $this->makeTeams(4);
        $schedule = $this->service->buildSchedule($teams);

        $pairs = $schedule->map(fn($f) => [
            min($f['home']->id, $f['away']->id),
            max($f['home']->id, $f['away']->id),
        ])->map(fn($p) => $p[0] . '-' . $p[1]);

        foreach ($pairs->countBy() as $pair => $count) {
            $this->assertEquals(2, $count, "Pair {$pair} should play exactly twice");
        }
    }

    public function test_home_away_balance_per_team(): void
    {
        $teams = $this->makeTeams(4);
        $schedule = $this->service->buildSchedule($teams);

        foreach ($teams as $team) {
            $homeCount = $schedule->filter(fn($f) => $f['home']->id === $team->id)->count();
            $awayCount = $schedule->filter(fn($f) => $f['away']->id === $team->id)->count();
            $this->assertEquals(3, $homeCount, "{$team->name} should have 3 home games");
            $this->assertEquals(3, $awayCount, "{$team->name} should have 3 away games");
        }
    }

    public function test_no_fixture_has_same_home_and_away_team(): void
    {
        $teams = $this->makeTeams(4);
        $schedule = $this->service->buildSchedule($teams);

        foreach ($schedule as $fixture) {
            $this->assertNotEquals($fixture['home']->id, $fixture['away']->id);
        }
    }

    public function test_second_leg_is_reverse_of_first_leg(): void
    {
        $teams = $this->makeTeams(4);
        $schedule = $this->service->buildSchedule($teams);

        $firstLeg  = $schedule->where('week', '<=', 3)->values();
        $secondLeg = $schedule->where('week', '>', 3)->values();

        foreach ($firstLeg as $i => $f) {
            $this->assertEquals($f['home']->id, $secondLeg[$i]['away']->id);
            $this->assertEquals($f['away']->id, $secondLeg[$i]['home']->id);
        }
    }
}
