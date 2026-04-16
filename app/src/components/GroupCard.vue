<script setup>
import { ref } from 'vue'
import StandingsTable from './StandingsTable.vue'
import FixtureRow from './FixtureRow.vue'

const props = defineProps({
  group:          { type: Object, required: true },
  lastPlayedWeek: { type: Number, default: null },
})

const tab = ref('standings')   // 'standings' | 'fixtures'
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

    <StandingsTable v-if="tab === 'standings'" :rows="group.standings" />

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
