<script setup>
import { computed } from 'vue'

const props = defineProps({
  events:     { type: Array,  required: true },
  homeTeamId: { type: String, required: true },
  awayTeamId: { type: String, required: true },
})

const ICONS = {
  goal:              '⚽',
  shot_saved:        '🧤',
  shot_blocked:      '🛡️',
  shot_off_target:   '↗️',
  shot_attempt:      '🎯',
  foul_committed:    '🟨',
  corner_won:        '🚩',
  corner_taken:      '🚩',
  free_kick_awarded: '🔵',
  interception:      '✋',
  tackle_won:        '💪',
  pass_failed:       '❌',
}

const LABELS = {
  goal:              'Goal',
  shot_saved:        'Shot Saved',
  shot_blocked:      'Shot Blocked',
  shot_off_target:   'Off Target',
  shot_attempt:      'Shot',
  foul_committed:    'Foul',
  corner_won:        'Corner',
  corner_taken:      'Corner',
  free_kick_awarded: 'Free Kick',
  interception:      'Interception',
  tackle_won:        'Tackle',
  pass_failed:       'Pass Lost',
}

const ZONE_LABELS = {
  defensive_third:  'Def',
  middle_third:     'Mid',
  attacking_third:  'Att',
  penalty_area:     'Box',
}

const FULL_WIDTH = new Set(['kickoff', 'half_time', 'full_time'])

// These events belong to the defending/saving team in the backend,
// but visually should appear on the OPPOSITE side:
//   shot_saved / shot_blocked → team_id is the shooter, show on goalkeeper's side
//   tackle_won                → team_id is the tackling defender, show on the attacked team's side
const FLIP_SIDE = new Set(['shot_saved', 'shot_blocked', 'tackle_won'])

// Enrich events with running score + side info
const enriched = computed(() => {
  let home = 0, away = 0
  return props.events.map(e => {
    let score = null
    if (e.event === 'goal') {
      if (e.team_id === props.homeTeamId) home++
      else away++
      score = `${home}–${away}`
    } else if (FULL_WIDTH.has(e.event)) {
      score = `${home}–${away}`
    }

    const flip   = FLIP_SIDE.has(e.event)
    const isHome = flip ? e.team_id === props.awayTeamId : e.team_id === props.homeTeamId
    const isAway = flip ? e.team_id === props.homeTeamId : e.team_id === props.awayTeamId

    return {
      ...e,
      score,
      isHome,
      isAway,
      isFullWidth: FULL_WIDTH.has(e.event),
      icon:        ICONS[e.event] ?? null,
      label:       LABELS[e.event] ?? e.event.replace(/_/g, ' '),
      zoneLabel:   ZONE_LABELS[e.zone] ?? null,
    }
  })
})

function fullLabel(e) {
  if (e.event === 'kickoff')   return 'Kickoff'
  if (e.event === 'half_time') return `Half Time · ${e.score}`
  if (e.event === 'full_time') return `Full Time · ${e.score}`
  return e.label
}
</script>

<template>
  <div class="tl2">
    <template v-for="(e, i) in enriched" :key="i">

      <!-- Kickoff / Half Time / Full Time — full-width centered row -->
      <div v-if="e.isFullWidth" class="tl2-full" :class="`tl2-full--${e.event}`">
        {{ fullLabel(e) }}
      </div>

      <!-- Normal event — split: home left / away right -->
      <div
        v-else
        class="tl2-row"
        :class="{
          'tl2-row--goal': e.event === 'goal',
          'tl2-row--foul': e.event === 'foul_committed',
          'tl2-row--alt':  i % 2 === 0,
        }"
      >
        <!-- Far-left zone badge (home events only) -->
        <div class="tl2-zone-col">
          <span v-if="e.isHome && e.zoneLabel" class="tl2-zone">{{ e.zoneLabel }}</span>
        </div>

        <!-- Home side (right-aligned) -->
        <div class="tl2-side tl2-side--home">
          <template v-if="e.isHome">
            <span class="tl2-label">
              {{ e.label }}
              <span v-if="e.score" class="tl2-score">({{ e.score }})</span>
            </span>
            <span v-if="e.icon" class="tl2-icon">{{ e.icon }}</span>
          </template>
        </div>

        <!-- Center: minute -->
        <div class="tl2-min">{{ e.minute }}'</div>

        <!-- Away side (left-aligned) -->
        <div class="tl2-side tl2-side--away">
          <template v-if="e.isAway">
            <span v-if="e.icon" class="tl2-icon">{{ e.icon }}</span>
            <span class="tl2-label">
              <span v-if="e.score" class="tl2-score">({{ e.score }})</span>
              {{ e.label }}
            </span>
          </template>
        </div>

        <!-- Far-right zone badge (away events only) -->
        <div class="tl2-zone-col">
          <span v-if="e.isAway && e.zoneLabel" class="tl2-zone">{{ e.zoneLabel }}</span>
        </div>
      </div>

    </template>
  </div>
</template>
