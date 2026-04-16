<?php

namespace App\Console\Commands;

use App\Exceptions\InvalidTournamentStateException;
use App\Services\DrawService;
use Illuminate\Console\Command;

class DrawTournamentCommand extends Command
{
    protected $signature = 'tournament:draw {--seed= : Optional integer seed for a reproducible draw}';
    protected $description = 'Run a tournament draw and generate group-stage fixtures';

    public function __construct(private readonly DrawService $drawService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $seed = $this->option('seed') !== null ? (int) $this->option('seed') : null;

        try {
            $tournament = $this->drawService->draw($seed);
        } catch (InvalidTournamentStateException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $this->info("Tournament draw complete. ID: {$tournament->id}");
        if ($seed !== null) {
            $this->line("Seed used: {$seed}");
        }

        foreach ($tournament->groups as $group) {
            $teams = $group->teams->pluck('name')->join(', ');
            $this->line("  Group {$group->name}: {$teams}");
        }

        return self::SUCCESS;
    }
}
