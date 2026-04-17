<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Models\TeamStat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DrawControllerTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_successful_draw_returns_201(): void
    {
        $this->seedTeams();

        $response = $this->postJson('/api/tournament/draw');

        $response->assertStatus(201)
                 ->assertJson(['success' => true])
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'tournament_id',
                         'groups' => [['name', 'teams', 'fixtures']],
                     ],
                 ]);
    }

    public function test_draw_without_teams_returns_422(): void
    {
        $response = $this->postJson('/api/tournament/draw');

        $response->assertStatus(422)
                 ->assertJson(['success' => false, 'code' => 'INVALID_STATE'])
                 ->assertJsonStructure(['success', 'message', 'code', 'errors']);
    }

    public function test_groups_count_matches_teams_per_pot(): void
    {
        $this->seedTeams(pots: 4, perPot: 4);

        $response = $this->postJson('/api/tournament/draw');

        $response->assertStatus(201);
        $this->assertCount(4, $response->json('data.groups'));
    }

    public function test_each_group_has_team_from_each_pot(): void
    {
        $this->seedTeams(pots: 4, perPot: 2);

        $response = $this->postJson('/api/tournament/draw');

        $response->assertStatus(201);
        foreach ($response->json('data.groups') as $group) {
            $pots = collect($group['teams'])->pluck('pot')->sort()->values()->all();
            $this->assertEquals([1, 2, 3, 4], $pots);
        }
    }
}
