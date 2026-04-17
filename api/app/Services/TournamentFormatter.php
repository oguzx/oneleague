<?php

namespace App\Services;

use App\Enums\FixtureStatus;
use App\Models\Fixture;
use App\Models\Group;
use App\Models\Tournament;

/**
 * Produces the canonical JSON representation of a tournament.
 * Always performs a fresh eager-load so post-play data is consistent.
 */
class TournamentFormatter
{
    public function __construct(private readonly LeagueTableService $leagueTable) {}

    public function format(Tournament $tournament): array
    {
        $tournament->load([
            'groups.teams',
            'groups.fixtures.homeTeam',
            'groups.fixtures.awayTeam',
            'groups.fixtures.events' => fn($q) => $q->select([
                'fixture_id', 'minute', 'second', 'event_type', 'zone', 'team_id', 'payload',
            ]),
        ]);

        $allFixtures = $tournament->groups->flatMap->fixtures;
        $currentWeek = $allFixtures
            ->filter(fn($f) => $f->status === FixtureStatus::Scheduled)
            ->min('match_week');
        $totalWeeks = $allFixtures->max('match_week');

        return [
            'id'                => $tournament->id,
            'name'              => $tournament->name,
            'current_week'      => $currentWeek,
            'total_weeks'       => $totalWeeks,
            'simulation_status' => $tournament->simulation_status?->value ?? 'idle',
            'groups'            => $tournament->groups->map(fn($g) => $this->formatGroup($g))->values(),
        ];
    }

    private function formatGroup(Group $group): array
    {
        $standings = $this->leagueTable->forGroup($group);

        $weeks = $group->fixtures
            ->sortBy('match_week')
            ->groupBy('match_week')
            ->map(fn($fixtures) => $fixtures->map(fn($f) => $this->formatFixture($f))->values());

        return [
            'id'         => $group->id,
            'name'       => $group->name,
            'standings'  => $standings->map(fn($r) => [
                'team_id'         => $r->teamId,
                'team'            => $r->teamName,
                'logo_url'        => $r->logoUrl,
                'played'          => $r->played,
                'won'             => $r->won,
                'drawn'           => $r->drawn,
                'lost'            => $r->lost,
                'goals_for'       => $r->goalsFor,
                'goals_against'   => $r->goalsAgainst,
                'goal_difference' => $r->goalDifference,
                'points'          => $r->points,
            ])->values(),
            'weeks' => $weeks,
        ];
    }

    private function formatFixture(Fixture $fixture): array
    {
        $completed = $fixture->status === FixtureStatus::Completed;

        return [
            'id'         => $fixture->id,
            'match_week' => $fixture->match_week,
            'home' => [
                'id'           => $fixture->homeTeam->id,
                'name'         => $fixture->homeTeam->name,
                'country_code' => $fixture->homeTeam->country_code,
                'logo_url'     => $fixture->homeTeam->logo_url,
            ],
            'away' => [
                'id'           => $fixture->awayTeam->id,
                'name'         => $fixture->awayTeam->name,
                'country_code' => $fixture->awayTeam->country_code,
                'logo_url'     => $fixture->awayTeam->logo_url,
            ],
            'status'             => $fixture->status->value,
            'weather'            => $fixture->weather?->value,
            'is_manually_edited' => $fixture->is_manually_edited,
            'score'  => $completed
                ? ['home' => $fixture->home_score, 'away' => $fixture->away_score]
                : null,
            'events' => $completed
                ? $fixture->events->map(fn($e) => [
                    'minute'  => $e->minute,
                    'second'  => $e->second,
                    'event'   => $e->event_type->value,
                    'team_id' => $e->team_id,
                    'zone'    => $e->zone->value,
                    'payload' => $e->payload ?? [],
                ])->values()
                : [],
        ];
    }
}
