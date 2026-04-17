<script setup>
import { computed, ref, onMounted, watch } from 'vue'

const props = defineProps({
  teams: { type: Array, required: true },
})

const withLogos = computed(() => props.teams.filter(t => t.logo_url))

const ROW_COUNT = 4

// Alternate rows: different speeds + opposing directions for depth effect
const ROW_CONFIGS = [
  { duration: 50, rtl: false },
  { duration: 37, rtl: true  },
  { duration: 55, rtl: false },
  { duration: 43, rtl: true  },
]

// Each row holds a shuffled copy of all teams,
// each logo gets its own random gap (generated once on mount)
const rows = ref([])

function shuffle(arr) {
  const a = [...arr]
  for (let i = a.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1))
    ;[a[i], a[j]] = [a[j], a[i]]
  }
  return a
}

function build() {
  if (!withLogos.value.length) return
  rows.value = Array.from({ length: ROW_COUNT }, () =>
    shuffle(withLogos.value).map(team => ({
      ...team,
      gap: Math.floor(Math.random() * 72) + 28, // 28–100 px
    }))
  )
}

onMounted(build)
watch(() => props.teams, build)
</script>

<template>
  <div v-if="rows.length" class="logo-bg" aria-hidden="true">
    <div
      v-for="(row, ri) in rows"
      :key="ri"
      class="logo-bg__row"
    >
      <div
        class="logo-bg__track"
        :class="{ 'logo-bg__track--rtl': ROW_CONFIGS[ri].rtl }"
        :style="{ '--dur': ROW_CONFIGS[ri].duration + 's' }"
      >
        <!--
          Two identical copies back-to-back.
          LTR: translateX(-50% → 0%)  — content enters from left, exits right
          RTL: translateX(0%  → -50%) — content enters from right, exits left
        -->
        <template v-for="copy in 2" :key="copy">
          <img
            v-for="team in row"
            :key="`${copy}-${team.id}`"
            :src="team.logo_url"
            :alt="team.name"
            class="logo-bg__img"
            :style="{ marginRight: team.gap + 'px' }"
            draggable="false"
          />
        </template>
      </div>
    </div>
  </div>
</template>
