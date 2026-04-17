import axios from 'axios'

const client = axios.create({
  baseURL: '/api',
  headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
})

client.interceptors.response.use(response => {
  if (response.data?.success === true) {
    response.data = response.data.data
  }
  return response
})

export default client
