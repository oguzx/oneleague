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

  reset: (id) =>
    client.post(`/tournaments/${id}/reset`).then(r => r.data),
}
