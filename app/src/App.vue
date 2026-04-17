<script setup>
import { ref, onMounted } from 'vue'
import { teamApi } from './api/teams'
import TeamLogoBanner from './components/TeamLogoBanner.vue'

const teams = ref([])

onMounted(async () => {
  try {
    teams.value = await teamApi.list()
  } catch {
    // decorative — silent failure is fine
  }
})
</script>

<template>
  <TeamLogoBanner :teams="teams" />
  <router-view :key="$route.fullPath" />
</template>
