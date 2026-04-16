<?php

namespace App\Console\Commands;

use App\Actions\PlayMatchAction;
use App\Data\LeagueTableRowData;
use App\Enums\FixtureStatus;
use App\Exceptions\InvalidTournamentStateException;
use App\Models\Fixture;
use Illuminate\Console\Command;

class PlayMatchCommand extends Command
{
    protected $signature = 'match:play
                            {--fixture= : Optional fixture UUID to simulate (omit to run all scheduled fixtures)}';

    protected $description = 'Simulate a scheduled fixture and persist the result';

    public function __construct(private readonly PlayMatchAction $action)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $fixtureId = $this->option('fixture');

        if ($fixtureId !== null) {
            $fixture = Fixture::find($fixtureId);
            if ($fixture === null) {
                $this->error("Fixture not found: {$fixtureId}");
                return self::FAILURE;
            }
            $fixtures = [$fixture];
        } else {
            $fixtures = Fixture::where('status', FixtureStatus::Scheduled)->get()->all();
        }

        foreach ($fixtures as $fixture) {
            if($fixture->status != FixtureStatus::Scheduled) {
                continue;
            }
            try {
                ['result' => $result, 'table' => $table] = $this->action->execute($fixture);
            } catch (InvalidTournamentStateException $e) {
                $this->error($e->getMessage());
                return self::FAILURE;
            }

            $this->info("Result: {$result->homeScore} – {$result->awayScore}");
            $this->newLine();
            $this->renderStandings($table->all());
        }

        return self::SUCCESS;
    }

    private function renderStandings(array $rows): void
    {
        $this->table(
            ['Team', 'P', 'W', 'D', 'L', 'GF', 'GA', 'GD', 'Pts'],
            array_map(fn(LeagueTableRowData $r) => [
                $r->teamName,
                $r->played,
                $r->won,
                $r->drawn,
                $r->lost,
                $r->goalsFor,
                $r->goalsAgainst,
                $r->goalDifference >= 0 ? "+{$r->goalDifference}" : (string) $r->goalDifference,
                $r->points,
            ], $rows)
        );
    }
}
