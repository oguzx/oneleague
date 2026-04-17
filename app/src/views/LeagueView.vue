<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { tournamentApi } from '../api/tournaments'
import GroupCard from '../components/GroupCard.vue'

const route   = useRoute()
const router  = useRouter()
const id      = route.params.id

const tournament  = ref(null)
const loading     = ref(true)
const busy        = ref(false)
const simulating  = ref(false)
const error       = ref(null)
const lastWeek    = ref(null)

let pollTimer    = null
let lastSeenWeek = null

const hasScheduled = computed(() =>
  tournament.value?.current_week != null
)

async function load() {
  loading.value = true
  error.value   = null
  try {
    tournament.value = await tournamentApi.get(id)
    if (tournament.value?.simulation_status === 'running' && !simulating.value) {
      simulating.value = true
      startPolling()
    }
  } catch {
    error.value = 'Failed to load tournament.'
  } finally {
    loading.value = false
  }
}

/** Refresh tournament data silently (no spinner) — used while simulation runs. */
async function silentRefresh() {
  try {
    tournament.value = await tournamentApi.get(id)
  } catch {
    // non-fatal during simulation
  }
}

async function playWeek() {
  if (busy.value || simulating.value) return
  busy.value  = true
  error.value = null
  const prevWeek = tournament.value?.current_week
  try {
    tournament.value = await tournamentApi.playWeek(id)
    lastWeek.value   = prevWeek
  } catch (e) {
    error.value = e.response?.data?.message ?? 'Play week failed.'
  } finally {
    busy.value = false
  }
}

async function playAll() {
  if (busy.value || simulating.value) return
  busy.value  = true
  error.value = null
  try {
    await tournamentApi.playAll(id)
    simulating.value = true
    lastWeek.value   = null
    startPolling()
  } catch (e) {
    error.value = e.response?.data?.message ?? 'Play all failed.'
  } finally {
    busy.value = false
  }
}

async function resetLeague() {
  if (!confirm('Reset all match results for this league?')) return
  stopPolling()
  simulating.value = false
  busy.value  = true
  error.value = null
  try {
    tournament.value = await tournamentApi.reset(id)
    lastWeek.value   = null
  } catch (e) {
    error.value = e.response?.data?.message ?? 'Reset failed.'
  } finally {
    busy.value = false
  }
}

function startPolling() {
  stopPolling()
  lastSeenWeek = tournament.value?.current_week ?? null

  pollTimer = setInterval(async () => {
    try {
      const s = await tournamentApi.simulationStatus(id)

      // Week advanced — pull fresh data immediately so standings update
      if (s.current_week !== lastSeenWeek) {
        lastSeenWeek = s.current_week
        await silentRefresh()
      }

      if (s.status === 'completed' || s.status === 'failed') {
        stopPolling()
        simulating.value = false
        if (s.status === 'failed') {
          error.value = 'Simulation failed. You can reset and try again.'
        }
        await silentRefresh()
      }
    } catch {
      // network hiccup — keep polling
    }
  }, 1500)
}

function stopPolling() {
  if (pollTimer !== null) {
    clearInterval(pollTimer)
    pollTimer = null
  }
}

onMounted(load)
onUnmounted(stopPolling)
</script>

<template>
  <div class="page">
    <header class="site-header">
      <button class="btn btn-ghost back-btn" @click="router.push('/')">← Back</button>
      <h1 class="site-title">OneLeague</h1>
    </header>

    <main class="league-view">
      <div v-if="loading" class="spinner-wrap">
        <div class="spinner" />
      </div>

      <template v-else-if="tournament">

        <!-- Simulation progress bar -->
        <div v-if="simulating" class="sim-bar">
          <div class="sim-bar__dot" />
          <span>
            Simulating
            <template v-if="tournament.current_week != null">
              — Week {{ tournament.current_week }} of {{ tournament.total_weeks }}
            </template>
            …
          </span>
        </div>

        <div class="league-header">
          <div>
            <h2 class="league-title">{{ tournament.name }}</h2>
            <p class="league-meta" v-if="hasScheduled && !simulating">
              Week {{ tournament.current_week }} of {{ tournament.total_weeks }}
            </p>
            <p class="league-meta league-meta--done" v-else-if="!simulating">
              All {{ tournament.total_weeks }} weeks completed
            </p>
          </div>
          <div class="action-bar">
            <button
              class="btn btn-primary"
              :disabled="busy || simulating || !hasScheduled"
              @click="playWeek"
            >
              {{ busy && !simulating ? '…' : `Play Week ${tournament.current_week ?? ''}` }}
            </button>
            <button
              class="btn btn-secondary"
              :disabled="busy || simulating || !hasScheduled"
              @click="playAll"
            >
              {{ simulating ? 'Simulating…' : 'Play All Weeks' }}
            </button>
            <button
              class="btn btn-danger"
              :disabled="busy || simulating"
              @click="resetLeague"
            >
              Reset League
            </button>
          </div>
        </div>

        <p v-if="error" class="error">{{ error }}</p>

        <div class="groups-grid">
          <GroupCard
            v-for="group in tournament.groups"
            :key="group.id"
            :group="group"
            :last-played-week="lastWeek"
          />
        </div>
      </template>

      <p v-else class="error">{{ error ?? 'Tournament not found.' }}</p>
    </main>
  </div>
</template>
