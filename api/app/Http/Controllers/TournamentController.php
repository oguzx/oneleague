<?php

namespace App\Http\Controllers;

use App\Enums\FixtureStatus;
use App\Models\Tournament;
use App\Services\TournamentFormatter;
use Illuminate\Http\JsonResponse;

class TournamentController extends Controller
{
    public function __construct(private readonly TournamentFormatter $formatter) {}

    /**
     * Return the single active tournament (has scheduled fixtures) and all past ones.
     */
    public function index(): JsonResponse
    {
        $tournaments = Tournament::with(['groups.fixtures' => fn($q) => $q->select([
            'id', 'group_id', 'match_week', 'status',
        ])])->latest()->get();

        $active = $tournaments->first(
            fn($t) => $t->groups->flatMap->fixtures
                ->contains(fn($f) => $f->status === FixtureStatus::Scheduled)
        );

        $past = $tournaments
            ->filter(fn($t) => $t->id !== $active?->id
                && $t->groups->flatMap->fixtures->isNotEmpty()
                && $t->groups->flatMap->fixtures->every(fn($f) => $f->status === FixtureStatus::Completed)
            )
            ->values();

        return response()->json([
            'active' => $active ? $this->stub($active) : null,
            'past'   => $past->map(fn($t) => $this->stub($t))->values(),
        ]);
    }

    public function show(Tournament $tournament): JsonResponse
    {
        return response()->json($this->formatter->format($tournament));
    }

    private function stub(Tournament $t): array
    {
        return ['id' => $t->id, 'name' => $t->name];
    }
}
