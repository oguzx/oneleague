<?php

namespace Tests\Unit;

use App\Exceptions\InvalidTournamentStateException;
use App\Models\Team;
use App\Models\TeamStat;
use App\Services\DrawService;
use App\Services\DrawValidator;
use App\Services\FixtureService;
use App\Services\GroupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DrawServiceTest extends TestCase
{
    use RefreshDatabase;

    private DrawService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DrawService(
            new DrawValidator(),
            new GroupService(),
            new FixtureService(),
        );
    }

    /** Create $perPot teams per pot across $pots pots, with unique country codes. */
    private function seedTeams(int $pots = 4, int $perPot = 2): void
    {
        $countries = ['ENG', 'GER', 'FRA', 'ESP', 'ITA', 'POR', 'NED', 'BEL', 'TUR', 'SCO', 'RUS', 'ARG', 'BRA', 'MEX', 'JPN', 'USA'];
        $i = 0;
        for ($pot = 1; $pot <= $pots; $pot++) {
            for ($j = 1; $j <= $perPot; $j++) {
                $team = Team::create(['name' => "Pot{$pot} Team{$j}", 'country_code' => $countries[$i++ % count($countries)]]);
                TeamStat::create(array_merge($this->defaultStats(), ['team_id' => $team->id, 'pot' => $pot]));
            }
        }
    }

    private function defaultStats(): array
    {
        return [
            'attack' => 80, 'midfield' => 80, 'defense' => 80, 'speed' => 80,
            'pass' => 80, 'shot' => 80, 'goalkeeper' => 80, 'finishing' => 80,
            'chance_creation' => 80, 'pressing' => 80, 'set_piece_strength' => 80,
            'winner_mentality' => 8, 'loser_mentality' => 8, 'consistency' => 80,
            'discipline' => 80, 'fatigue_resistance' => 80, 'big_match_performance' => 80,
            'resilience' => 80, 'manager_influence' => 8, 'squad_depth' => 25,
            'injury_risk' => 5, 'star_players_count' => 3, 'home_advantage' => 7,
            'min_temp_performance' => 0, 'max_temp_performance' => 30,
        ];
    }

    public function test_successful_draw_creates_correct_number_of_groups(): void
    {
        $this->seedTeams(pots: 4, perPot: 8);
        $tournament = $this->service->draw();

        $this->assertCount(8, $tournament->groups);
    }

    public function test_each_group_has_exactly_4_teams(): void
    {
        $this->seedTeams(pots: 4, perPot: 4);
        $tournament = $this->service->draw();

        foreach ($tournament->groups as $group) {
            $this->assertCount(4, $group->teams);
        }
    }

    public function test_each_group_has_one_team_per_pot(): void
    {
        $this->seedTeams(pots: 4, perPot: 4);
        $tournament = $this->service->draw();

        foreach ($tournament->groups as $group) {
            $pots = $group->teams->map(fn($t) => $t->stat->pot)->sort()->values()->all();
            $this->assertEquals([1, 2, 3, 4], $pots);
        }
    }

    public function test_no_team_is_duplicated_across_groups(): void
    {
        $this->seedTeams(pots: 4, perPot: 4);
        $tournament = $this->service->draw();

        $allTeamIds = $tournament->groups->flatMap(fn($g) => $g->teams->pluck('id'));
        $this->assertEquals($allTeamIds->count(), $allTeamIds->unique()->count());
    }

    public function test_each_group_has_12_fixtures(): void
    {
        $this->seedTeams(pots: 4, perPot: 2);
        $tournament = $this->service->draw();

        foreach ($tournament->groups as $group) {
            $this->assertCount(12, $group->fixtures);
        }
    }

    public function test_fails_with_no_teams(): void
    {
        $this->expectException(InvalidTournamentStateException::class);
        $this->service->draw();
    }

    public function test_fails_when_team_missing_stat(): void
    {
        Team::create(['name' => 'No Stat FC', 'country_code' => 'ENG']);

        $this->expectException(InvalidTournamentStateException::class);
        $this->expectExceptionMessageMatches('/missing stat/i');
        $this->service->draw();
    }

    public function test_fails_when_pot_count_is_not_4(): void
    {
        $this->seedTeams(pots: 3, perPot: 4);

        $this->expectException(InvalidTournamentStateException::class);
        $this->expectExceptionMessageMatches('/Exactly 4 pots/i');
        $this->service->draw();
    }

    public function test_fails_when_pots_have_unequal_sizes(): void
    {
        // 3 teams in pot 1, 4 in pots 2–4
        for ($pot = 1; $pot <= 4; $pot++) {
            $count = $pot === 1 ? 3 : 4;
            for ($j = 1; $j <= $count; $j++) {
                $team = Team::create(['name' => "P{$pot}T{$j}", 'country_code' => 'ENG']);
                TeamStat::create(array_merge($this->defaultStats(), ['team_id' => $team->id, 'pot' => $pot]));
            }
        }

        $this->expectException(InvalidTournamentStateException::class);
        $this->expectExceptionMessageMatches('/equal team counts/i');
        $this->service->draw();
    }

    public function test_fails_when_pot_numbers_are_not_consecutive(): void
    {
        foreach ([1, 2, 4, 5] as $pot) { // pot 3 is missing
            for ($j = 1; $j <= 2; $j++) {
                $team = Team::create(['name' => "P{$pot}T{$j}", 'country_code' => 'ENG']);
                TeamStat::create(array_merge($this->defaultStats(), ['team_id' => $team->id, 'pot' => $pot]));
            }
        }

        $this->expectException(InvalidTournamentStateException::class);
        $this->expectExceptionMessageMatches('/consecutive/i');
        $this->service->draw();
    }
}
