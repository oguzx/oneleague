<script setup>
import { ref, computed } from 'vue'
import StandingsTable from './StandingsTable.vue'
import FixtureRow from './FixtureRow.vue'

const props = defineProps({
  group:          { type: Object, required: true },
  lastPlayedWeek: { type: Number, default: null },
})

const tab          = ref('standings')
const selectedWeek = ref(null)   // null = current (full) standings

// ─── Week history helpers ─────────────────────────────────────────────────

/** Flatten all fixtures from every week into one array. */
const allFixtures = computed(() =>
  Object.values(props.group.weeks ?? {}).flat()
)

/** Only weeks where every fixture is completed. */
const completedWeeks = computed(() =>
  Object.entries(props.group.weeks ?? {})
    .filter(([, fixtures]) => fixtures.every(f => f.status === 'completed'))
    .map(([w]) => Number(w))
    .sort((a, b) => a - b)
)

/**
 * Build a standings table from scratch using only completed fixtures
 * up to and including `upToWeek`.
 * All teams are always present (initialised from current standings) so
 * early-week snapshots still show the full group with 0-row entries.
 */
function standingsUpToWeek(upToWeek) {
  // Seed every team with zeroed stats
  const table = {}
  for (const row of props.group.standings) {
    table[row.team_id] = {
      team_id:        row.team_id,
      team:           row.team,
      logo_url:       row.logo_url,
      played:         0,
      won:            0,
      drawn:          0,
      lost:           0,
      goals_for:      0,
      goals_against:  0,
      goal_difference: 0,
      points:         0,
    }
  }

  for (const f of allFixtures.value) {
    if (f.status !== 'completed' || f.match_week > upToWeek || !f.score) continue

    const h = table[f.home.id]
    const a = table[f.away.id]
    if (!h || !a) continue

    const hs = f.score.home
    const as = f.score.away

    h.played++; a.played++
    h.goals_for    += hs; h.goals_against += as
    a.goals_for    += as; a.goals_against += hs

    if (hs > as)      { h.won++;   a.lost++;  h.points += 3 }
    else if (hs < as) { a.won++;   h.lost++;  a.points += 3 }
    else              { h.drawn++; a.drawn++; h.points++;  a.points++ }
  }

  return Object.values(table)
    .map(r => ({ ...r, goal_difference: r.goals_for - r.goals_against }))
    .sort((a, b) =>
      b.points          - a.points          ||
      b.goal_difference - a.goal_difference ||
      b.goals_for       - a.goals_for
    )
}

/** Rows shown in the standings tab — live or historical. */
const displayedRows = computed(() =>
  selectedWeek.value === null
    ? props.group.standings
    : standingsUpToWeek(selectedWeek.value)
)
</script>

<template>
  <div class="group-card">
    <div class="group-header">
      <h3 class="group-title">Group {{ group.name }}</h3>
      <div class="tab-bar">
        <button
          class="tab-btn"
          :class="{ active: tab === 'standings' }"
          @click="tab = 'standings'"
        >Standings</button>
        <button
          class="tab-btn"
          :class="{ active: tab === 'fixtures' }"
          @click="tab = 'fixtures'"
        >Fixtures</button>
      </div>
    </div>

    <!-- Standings tab -->
    <template v-if="tab === 'standings'">

      <!-- Week snapshot selector — only shown when at least one week is done -->
      <div v-if="completedWeeks.length" class="week-filter">
        <button
          class="wf-btn"
          :class="{ 'wf-btn--active': selectedWeek === null }"
          @click="selectedWeek = null"
        >Now</button>
        <button
          v-for="w in completedWeeks"
          :key="w"
          class="wf-btn"
          :class="{ 'wf-btn--active': selectedWeek === w }"
          @click="selectedWeek = w"
        >W{{ w }}</button>
      </div>

      <StandingsTable :rows="displayedRows" />
    </template>

    <!-- Fixtures tab -->
    <div v-else class="fixtures-list">
      <div v-for="(fixtures, week) in group.weeks" :key="week" class="week-block">
        <div class="week-label">Week {{ week }}</div>
        <FixtureRow
          v-for="fixture in fixtures"
          :key="fixture.id"
          :fixture="fixture"
          :auto-expand="Number(week) === lastPlayedWeek"
        />
      </div>
    </div>
  </div>
</template>
