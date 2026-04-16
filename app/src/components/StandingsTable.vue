<script setup>
defineProps({
  rows: { type: Array, required: true },
})
</script>

<template>
  <div class="standings-wrap">
    <table class="standings-table">
      <thead>
        <tr>
          <th class="col-team">Team</th>
          <th>P</th><th>W</th><th>D</th><th>L</th>
          <th>GF</th><th>GA</th><th>GD</th><th>Pts</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(row, i) in rows" :key="row.team_id" :class="{ 'row-top': i < 2 }">
          <td class="col-team">
            <img
              v-if="row.logo_url"
              :src="row.logo_url"
              :alt="row.team"
              class="standing-logo"
            />
            <span v-else class="standing-logo standing-logo--placeholder" />
            <span>{{ row.team }}</span>
          </td>
          <td>{{ row.played }}</td>
          <td>{{ row.won }}</td>
          <td>{{ row.drawn }}</td>
          <td>{{ row.lost }}</td>
          <td>{{ row.goals_for }}</td>
          <td>{{ row.goals_against }}</td>
          <td :class="row.goal_difference > 0 ? 'gd-pos' : row.goal_difference < 0 ? 'gd-neg' : ''">
            {{ row.goal_difference > 0 ? '+' : '' }}{{ row.goal_difference }}
          </td>
          <td class="col-pts">{{ row.points }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
