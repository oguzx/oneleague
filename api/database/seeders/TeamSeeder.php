<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Models\TeamStat;
use Illuminate\Database\Seeder;

class TeamSeeder extends Seeder
{
    private const COUNTRY_CODES = [
        'England'     => 'ENG',
        'Spain'       => 'ESP',
        'Germany'     => 'DEU',
        'France'      => 'FRA',
        'Portugal'    => 'PRT',
        'Italy'       => 'ITA',
        'Netherlands' => 'NLD',
        'Ukraine'     => 'UKR',
        'Austria'     => 'AUT',
        'Serbia'      => 'SRB',
        'Denmark'     => 'DNK',
        'Switzerland' => 'CHE',
        'Turkey'      => 'TUR',
        'Scotland'    => 'SCO',
        'Belgium'     => 'BEL',
    ];

    public function run(): void
    {
        $teams = json_decode(
            file_get_contents(database_path('seeders/data/teams.json')),
            true
        );

        foreach ($teams as $data) {
            $team = Team::create([
                'name'         => $data['name'],
                'color'        => $data['color'],
                'country_code' => self::COUNTRY_CODES[$data['country']] ?? null,
            ]);

            TeamStat::create([
                'team_id'              => $team->id,
                'attack'               => $data['attack'],
                'midfield'             => $data['midfield'],
                'defense'              => $data['defense'],
                'speed'                => $data['speed'],
                'pass'                 => $data['pass'],
                'shot'                 => $data['shot'],
                'goalkeeper'           => $data['goalkeeper'],
                'finishing'            => $data['finishing'],
                'chance_creation'      => $data['chance_creation'],
                'pressing'             => $data['pressing'],
                'set_piece_strength'   => $data['set_piece_strength'],
                'winner_mentality'     => $data['winner_mentality'],
                'loser_mentality'      => $data['loser_mentality'],
                'consistency'          => $data['consistency'],
                'discipline'           => $data['discipline'],
                'fatigue_resistance'   => $data['fatigue_resistance'],
                'big_match_performance'=> $data['big_match_performance'],
                'resilience'           => $data['resilience'],
                'manager_influence'    => $data['manager_influence'],
                'squad_depth'          => $data['squad_depth'],
                'injury_risk'          => $data['injury_risk'],
                'star_players_count'   => $data['star_players_count'],
                'pot'                  => $data['pot'],
                'home_advantage'       => $data['home_advantage'],
                'min_temp_performance' => $data['min_temp_performance'],
                'max_temp_performance' => $data['max_temp_performance'],
            ]);
        }
    }
}
