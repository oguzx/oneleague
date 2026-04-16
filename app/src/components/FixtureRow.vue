<script setup>
import { ref, watch } from 'vue'
import EventTimeline from './EventTimeline.vue'

const props = defineProps({
  fixture:    { type: Object,  required: true },
  autoExpand: { type: Boolean, default: false },
})

const open = ref(props.autoExpand && props.fixture.events?.length > 0)

watch(() => props.autoExpand, (val) => {
  if (val && props.fixture.events?.length > 0) open.value = true
})

function toggle() {
  if (props.fixture.events?.length) open.value = !open.value
}

const completed = props.fixture.status === 'completed'
</script>

<template>
  <div class="fixture-row" :class="{ 'fixture-row--completed': completed }">
    <div class="fixture-header" @click="toggle">

      <!-- Home team -->
      <div class="fixture-team fixture-home">
        <span class="team-name">{{ fixture.home.name }}</span>
        <img
          v-if="fixture.home.logo_url"
          :src="fixture.home.logo_url"
          :alt="fixture.home.name"
          class="team-logo"
        />
        <span v-else class="team-logo team-logo--placeholder" />
      </div>

      <!-- Score / vs -->
      <div class="fixture-score">
        <template v-if="fixture.score">
          <span class="score-num">{{ fixture.score.home }}</span>
          <span class="score-sep">–</span>
          <span class="score-num">{{ fixture.score.away }}</span>
        </template>
        <span v-else class="score-vs">vs</span>
      </div>

      <!-- Away team -->
      <div class="fixture-team fixture-away">
        <img
          v-if="fixture.away.logo_url"
          :src="fixture.away.logo_url"
          :alt="fixture.away.name"
          class="team-logo"
        />
        <span v-else class="team-logo team-logo--placeholder" />
        <span class="team-name">{{ fixture.away.name }}</span>
      </div>

      <div class="fixture-toggle" v-if="fixture.events?.length">
        {{ open ? '▲' : '▼' }}
      </div>
    </div>

    <EventTimeline
      v-if="open && fixture.events?.length"
      :events="fixture.events"
      :home-team-id="fixture.home.id"
      :away-team-id="fixture.away.id"
    />
  </div>
</template>
