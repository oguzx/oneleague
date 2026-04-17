<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { tournamentApi } from '../api/tournaments'

const router  = useRouter()
const loading = ref(true)
const drawing = ref(false)
const error   = ref(null)
const active  = ref(null)
const past    = ref([])

async function load() {
  loading.value = true
  error.value   = null
  try {
    const data   = await tournamentApi.list()
    active.value = data.active
    past.value   = data.past
  } catch {
    error.value = 'Failed to load tournaments.'
  } finally {
    loading.value = false
  }
}

async function draw() {
  drawing.value = true
  error.value   = null
  try {
    const result = await tournamentApi.draw()
    router.push(`/league/${result.tournament_id}`)
  } catch (e) {
    error.value = e.response?.data?.message ?? 'Draw failed.'
    drawing.value = false
  }
}

function open(id) {
  router.push(`/league/${id}`)
}

onMounted(load)
</script>

<template>
  <div class="page">
    <header class="site-header">
      <h1 class="site-title">OneLeague</h1>
    </header>

    <main class="home">
      <div v-if="loading" class="spinner-wrap">
        <div class="spinner" />
      </div>

      <div v-else>
        <p v-if="error" class="error">{{ error }}</p>

        <section class="section">
          <h2 class="section-title">Active League</h2>
          <div v-if="active" class="league-card" @click="open(active.id)">
            <span class="league-name">{{ active.name }}</span>
            <span class="league-arrow">→</span>
          </div>
          <div v-else class="empty-state">
            <p>No active league.</p>
            <button class="btn btn-primary" :disabled="drawing" @click="draw">
              {{ drawing ? 'Drawing…' : 'Draw New League' }}
            </button>
          </div>
        </section>

        <section v-if="past.length" class="section">
          <h2 class="section-title">Past Leagues</h2>
          <div
            v-for="t in past"
            :key="t.id"
            class="league-card league-card--past"
            @click="open(t.id)"
          >
            <span class="league-name">{{ t.name }}</span>
            <span class="league-arrow">→</span>
          </div>
        </section>
      </div>
    </main>
  </div>
</template>
