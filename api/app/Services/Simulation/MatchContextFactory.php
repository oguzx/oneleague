<?php

namespace App\Services\Simulation;

use App\Data\MatchContextData;
use App\Data\TeamStrengthProfileData;
use App\Models\Fixture;
use App\Models\TeamStat;

class MatchContextFactory
{
    public function build(Fixture $fixture): MatchContextData
    {
        $homeStat = $fixture->homeTeam->stat;
        $awayStat = $fixture->awayTeam->stat;

        $homeProfile = $this->buildProfile($fixture->home_team_id, $homeStat);
        $awayProfile = $this->buildProfile($fixture->away_team_id, $awayStat);

        return new MatchContextData(
            fixtureId:                       $fixture->id,
            homeTeamId:                      $fixture->home_team_id,
            awayTeamId:                      $fixture->away_team_id,
            homeProfile:                     $homeProfile,
            awayProfile:                     $awayProfile,
            homeAdvantageFactor:             $homeStat->home_advantage / 10.0,
            tempoFactor:                     $this->tempoFactor($homeStat, $awayStat),
            refStrictnessFactor:             $this->refStrictnessFactor($homeStat, $awayStat),
            expectedHomeAttackingPressure:   $this->attackingPressure($homeProfile, $awayProfile),
            expectedAwayAttackingPressure:   $this->attackingPressure($awayProfile, $homeProfile),
        );
    }

    private function buildProfile(string $teamId, TeamStat $stat): TeamStrengthProfileData
    {
        return new TeamStrengthProfileData(
            teamId:              $teamId,
            attack:              $stat->attack / 100.0,
            defense:             $stat->defense / 100.0,
            midfield:            $stat->midfield / 100.0,
            finishing:           $stat->finishing / 100.0,
            goalkeeper:          $stat->goalkeeper / 100.0,
            pressing:            $stat->pressing / 100.0,
            setPiece:            $stat->set_piece_strength / 100.0,
            consistency:         $stat->consistency / 100.0,
            fatigueResistance:   $stat->fatigue_resistance / 100.0,
            bigMatchPerformance: $stat->big_match_performance / 100.0,
            winnerMentality:     $stat->winner_mentality / 10.0,
            loserMentality:      $stat->loser_mentality / 10.0,
            homeAdvantageRaw:    $stat->home_advantage,
        );
    }

    private function tempoFactor(TeamStat $home, TeamStat $away): float
    {
        return ($home->midfield + $home->speed + $away->midfield + $away->speed) / 400.0;
    }

    private function refStrictnessFactor(TeamStat $home, TeamStat $away): float
    {
        // Lower average discipline → stricter referee / more fouls awarded
        $avgDiscipline = ($home->discipline + $away->discipline) / 200.0;
        return 1.0 - ($avgDiscipline * 0.4);
    }

    private function attackingPressure(
        TeamStrengthProfileData $offense,
        TeamStrengthProfileData $defense,
    ): float {
        $raw = ($offense->attack + $offense->finishing + $offense->pressing) / 3.0;
        return $raw / (1.0 + $defense->defense * 0.5);
    }
}
