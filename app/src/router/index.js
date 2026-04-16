import { createRouter, createWebHistory } from 'vue-router'
import HomeView from '../views/HomeView.vue'
import LeagueView from '../views/LeagueView.vue'

const routes = [
  { path: '/', component: HomeView },
  { path: '/league/:id', component: LeagueView },
]

export default createRouter({
  history: createWebHistory(),
  routes,
})
