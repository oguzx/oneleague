<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { tournamentApi } from '../api/tournaments'
import GroupCard from '../components/GroupCard.vue'

const route   = useRoute()
const router  = useRouter()
const id      = route.params.id

const tournament  = ref(null)
const loading     = ref(true)
const busy        = ref(false)
const error       = ref(null)
const lastWeek    = ref(null)   // week number just played (for timeline auto-expand)

const hasScheduled = computed(() =>
  tournament.value?.current_week != null
)

async function load() {
  loading.value = true
  error.value   = null
  try {
    tournament.value = await tournamentApi.get(id)
  } catch {
    error.value = 'Failed to load tournament.'
  } finally {
    loading.value = false
  }
}

async function playWeek() {
  if (busy.value) return
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
  if (busy.value) return
  busy.value  = true
  error.value = null
  try {
    tournament.value = await tournamentApi.playAll(id)
    lastWeek.value   = null   // all weeks played — expand nothing by default
  } catch (e) {
    error.value = e.response?.data?.message ?? 'Play all failed.'
  } finally {
    busy.value = false
  }
}

async function resetLeague() {
  if (!confirm('Reset all match results for this league?')) return
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

onMounted(load)
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
        <div class="league-header">
          <div>
            <h2 class="league-title">{{ tournament.name }}</h2>
            <p class="league-meta" v-if="hasScheduled">
              Week {{ tournament.current_week }} of {{ tournament.total_weeks }}
            </p>
            <p class="league-meta league-meta--done" v-else>
              All {{ tournament.total_weeks }} weeks completed
            </p>
          </div>
          <div class="action-bar">
            <button
              class="btn btn-primary"
              :disabled="busy || !hasScheduled"
              @click="playWeek"
            >
              {{ busy ? '…' : `Play Week ${tournament.current_week ?? ''}` }}
            </button>
            <button
              class="btn btn-secondary"
              :disabled="busy || !hasScheduled"
              @click="playAll"
            >
              {{ busy ? '…' : 'Play All Weeks' }}
            </button>
            <button
              class="btn btn-danger"
              :disabled="busy"
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
