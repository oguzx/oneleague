<?php

namespace App\Services\Simulation;

use App\Data\MatchContextData;
use App\Data\TeamStrengthProfileData;
use App\Models\Fixture;
use App\Models\TeamStat;

class MatchContextFactory
{
    /** Stats stored as 0–100 integers; normalise to 0–1 floats. */
    private const STAT_SCALE = 100.0;

    /** Home advantage, winner/loser mentality stored as 0–10 integers. */
    private const MENTALITY_SCALE = 10.0;

    /** Tempo = sum of 4 stats (each 0–100), so max = 400. */
    private const TEMPO_DIVISOR = 400.0;

    /** Ref strictness uses average of 2 discipline stats (each 0–100). */
    private const DISCIPLINE_DIVISOR = 200.0;

    /** How much average discipline reduces foul frequency (0–1 weight). */
    private const DISCIPLINE_STRICTNESS_WEIGHT = 0.4;

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
            homeAdvantageFactor:             $homeStat->home_advantage / self::MENTALITY_SCALE,
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
            attack:              $stat->attack              / self::STAT_SCALE,
            defense:             $stat->defense             / self::STAT_SCALE,
            midfield:            $stat->midfield            / self::STAT_SCALE,
            finishing:           $stat->finishing           / self::STAT_SCALE,
            goalkeeper:          $stat->goalkeeper          / self::STAT_SCALE,
            pressing:            $stat->pressing            / self::STAT_SCALE,
            setPiece:            $stat->set_piece_strength  / self::STAT_SCALE,
            consistency:         $stat->consistency         / self::STAT_SCALE,
            fatigueResistance:   $stat->fatigue_resistance  / self::STAT_SCALE,
            bigMatchPerformance: $stat->big_match_performance / self::STAT_SCALE,
            winnerMentality:     $stat->winner_mentality    / self::MENTALITY_SCALE,
            loserMentality:      $stat->loser_mentality     / self::MENTALITY_SCALE,
            homeAdvantageRaw:    $stat->home_advantage,
        );
    }

    private function tempoFactor(TeamStat $home, TeamStat $away): float
    {
        return ($home->midfield + $home->speed + $away->midfield + $away->speed) / self::TEMPO_DIVISOR;
    }

    private function refStrictnessFactor(TeamStat $home, TeamStat $away): float
    {
        // Lower average discipline → stricter referee → more fouls awarded
        $avgDiscipline = ($home->discipline + $away->discipline) / self::DISCIPLINE_DIVISOR;
        return 1.0 - ($avgDiscipline * self::DISCIPLINE_STRICTNESS_WEIGHT);
    }

    private function attackingPressure(
        TeamStrengthProfileData $offense,
        TeamStrengthProfileData $defense,
    ): float {
        $raw = ($offense->attack + $offense->finishing + $offense->pressing) / 3.0;
        return $raw / (1.0 + $defense->defense * 0.5);
    }
}
