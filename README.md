# OneLeague

A Champions League-style group stage football tournament simulator.

Draws 32 teams into 8 groups, generates a double round-robin fixture list for each group, and simulates matches tick-by-tick using a probabilistic event engine driven by team stats, fatigue, and momentum. Matches can be played one at a time, week by week, or as a full async tournament simulation.

---

## Tech Stack

| | |
|---|---|
| Backend | Laravel 13 / PHP 8.3 |
| Database | PostgreSQL 16 |
| Frontend | Vue 3 / Vite |
| Infrastructure | Docker Compose |

---

## Quick Start

Bring everything up with a single command:

```bash
make up
```

This will:
1. Copy `api/.env.example` → `api/.env` if not already present
2. Build and start all Docker containers
3. Run `composer update`
4. Run `migrate:fresh --seed` to set up the database
5. Open the browser automatically once the frontend is ready

### Other Commands

```bash
make fresh_seed   # Wipe and re-seed the database
make test         # Run the full test suite
```

### Services

| Service | Address |
|---|---|
| Frontend | http://localhost:5173 |
| API | http://localhost:8080/api |
| PostgreSQL | localhost:5432 |

---

## How It Works

1. **Draw** — 32 teams seeded into 4 pots are drawn into 8 groups, with no two teams from the same country allowed in the same group.
2. **Fixtures** — A double round-robin schedule is generated per group (12 matches, 6 weeks).
3. **Simulation** — Each match is broken into 360 ticks (15 seconds each). Every tick, a probability pipeline driven by team stats, fatigue, and momentum determines the next event.
4. **Async mode** — "Play All Weeks" runs the entire tournament in the background via Laravel Job Batches; the frontend polls progress every 1.5 seconds.

---

## Project Structure

```
OneLeague/
├── api/          # Laravel 13 REST API
├── app/          # Vue 3 frontend
├── docker/       # Dockerfiles
├── docker-compose.yml
├── Makefile
└── README.md
```