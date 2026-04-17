import client from './client'

export const tournamentApi = {
  list: () =>
    client.get('/tournaments').then(r => r.data),

  get: (id) =>
    client.get(`/tournaments/${id}`).then(r => r.data),

  draw: () =>
    client.post('/tournament/draw').then(r => r.data),

  playWeek: (id) =>
    client.post(`/tournaments/${id}/play-week`).then(r => r.data),

  playAll: (id) =>
    client.post(`/tournaments/${id}/play-all`).then(r => r.data),

  simulationStatus: (id) =>
    client.get(`/tournaments/${id}/simulation-status`).then(r => r.data),

  reset: (id) =>
    client.post(`/tournaments/${id}/reset`).then(r => r.data),

  regenerate: (id) =>
    client.post(`/tournaments/${id}/regenerate`).then(r => r.data),

  editFixture: (fixtureId, homeScore, awayScore) =>
    client.put(`/fixtures/${fixtureId}`, { home_score: homeScore, away_score: awayScore }).then(r => r.data),
}
