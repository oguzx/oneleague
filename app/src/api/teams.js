import client from './client'

export const teamApi = {
  list: () => client.get('/teams').then(r => r.data),
}
