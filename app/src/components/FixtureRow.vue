<script setup>
import { ref, computed, watch } from 'vue'
import EventTimeline from './EventTimeline.vue'
import { tournamentApi } from '../api/tournaments'

const props = defineProps({
  fixture:       { type: Object,  required: true },
  scoreEditMode: { type: Boolean, default: false },
})

const emit = defineEmits(['fixture-edited'])

const open      = ref(false)
const editing   = ref(false)
const editHome  = ref(0)
const editAway  = ref(0)
const saving    = ref(false)
const editError = ref(null)

// Close edit form when score edit mode is turned off
watch(() => props.scoreEditMode, (val) => {
  if (!val) editing.value = false
})

const completed = computed(() => props.fixture.status === 'completed')

const weatherIcon = {
  clear: '☀️',
  rain:  '🌧️',
  snow:  '❄️',
  heat:  '🌡️',
  windy: '💨',
  foggy: '🌫️',
}

function toggle() {
  if (editing.value) return
  if (props.fixture.events?.length) open.value = !open.value
}

function openEdit() {
  editHome.value  = props.fixture.score?.home ?? 0
  editAway.value  = props.fixture.score?.away ?? 0
  editError.value = null
  editing.value   = true
  open.value      = false
}

function cancelEdit() {
  editing.value = false
}

async function saveEdit() {
  if (saving.value) return
  saving.value    = true
  editError.value = null
  try {
    await tournamentApi.editFixture(props.fixture.id, editHome.value, editAway.value)
    editing.value = false
    emit('fixture-edited')
  } catch (e) {
    editError.value = e.response?.data?.message ?? 'Save failed.'
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div class="fixture-row" :class="{ 'fixture-row--completed': completed }">

    <!-- Inline score edit form -->
    <div v-if="editing" class="fixture-edit-form">
      <span class="team-name fixture-edit-home-name">{{ fixture.home.name }}</span>
      <input
        v-model.number="editHome"
        type="number" min="0" max="99"
        class="score-input"
        :disabled="saving"
      />
      <span class="score-sep">–</span>
      <input
        v-model.number="editAway"
        type="number" min="0" max="99"
        class="score-input"
        :disabled="saving"
      />
      <span class="team-name fixture-edit-away-name">{{ fixture.away.name }}</span>
      <div class="fixture-edit-actions">
        <button class="btn btn-primary btn-xs" :disabled="saving" @click="saveEdit">
          {{ saving ? '…' : 'Save' }}
        </button>
        <button class="btn btn-ghost btn-xs" :disabled="saving" @click="cancelEdit">Cancel</button>
      </div>
      <p v-if="editError" class="fixture-edit-error">{{ editError }}</p>
    </div>

    <!-- Normal fixture header -->
    <div v-else class="fixture-header" @click="toggle">

      <!-- Left gutter (mirrors right-side actions for centering) -->
      <div class="fixture-gutter" />

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

      <!-- Edit icon (score edit mode) -->
      <button
        v-if="scoreEditMode"
        class="fixture-edit-btn fixture-gutter fixture-edit-btn--glow"
        title="Edit score"
        @click.stop="openEdit"
      >✏️</button>

      <!-- Manual edited badge / expand toggle -->
      <template v-else-if="completed">
        <span v-if="fixture.is_manually_edited" class="manual-edited-badge fixture-gutter">✎</span>
        <div v-else-if="fixture.events?.length" class="fixture-toggle fixture-gutter">
          {{ open ? '▲' : '▼' }}
        </div>
        <div v-else class="fixture-gutter" />
      </template>

      <div v-else-if="fixture.events?.length" class="fixture-toggle fixture-gutter">
        {{ open ? '▲' : '▼' }}
      </div>
      <div v-else class="fixture-gutter" />
    </div>

    <!-- Weather meta line — shown only for completed, non-manually-edited fixtures -->
    <div
      v-if="completed && !fixture.is_manually_edited && fixture.weather"
      class="fixture-weather"
    >
      <span>{{ weatherIcon[fixture.weather] ?? '' }}</span>
      <span>{{ fixture.weather }}</span>
    </div>

    <EventTimeline
      v-if="open && fixture.events?.length"
      :events="fixture.events"
      :home-team-id="fixture.home.id"
      :away-team-id="fixture.away.id"
    />
  </div>
</template>
