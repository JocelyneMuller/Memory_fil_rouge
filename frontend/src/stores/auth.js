import { defineStore } from 'pinia'
import { ref } from 'vue'

const API_BASE = import.meta.env.VITE_AUTH_API_URL || 'http://localhost:8888/PFR/Memory/backend/?loc=auth&action='
const TOKEN_KEY = import.meta.env.VITE_JWT_STORAGE_KEY || 'authToken'
const USER_KEY = import.meta.env.VITE_JWT_USER_KEY || 'authUser'

export const useAuthStore = defineStore('auth', () => {
  const token = ref(localStorage.getItem(TOKEN_KEY) || null)
  const user = ref(JSON.parse(localStorage.getItem(USER_KEY) || 'null'))
  const loading = ref(false)
  const error = ref(null)

  async function login(email, password) {
    loading.value = true
    error.value = null
    try {
      const res = await fetch(API_BASE + 'login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, password })
      })
      const json = await res.json()
      if (!json.success) throw new Error(json.error || 'Erreur de connexion')
      token.value = json.data.token
      user.value = json.data.user
      localStorage.setItem(TOKEN_KEY, token.value)
      localStorage.setItem(USER_KEY, JSON.stringify(user.value))
      return json
    } catch (e) {
      error.value = e.message
      throw e
    } finally {
      loading.value = false
    }
  }

  function logout() {
    token.value = null
    user.value = null
    localStorage.removeItem(TOKEN_KEY)
    localStorage.removeItem(USER_KEY)
  }

  async function fetchMe() {
    if (!token.value) throw new Error('Token manquant')
    loading.value = true
    try {
      const res = await fetch(API_BASE + 'me', {
        method: 'GET',
        headers: { Authorization: `Bearer ${token.value}` }
      })
      const json = await res.json()
      if (!json.success) throw new Error(json.error || 'Impossible de récupérer l\'utilisateur')
      user.value = json.data.user
      return json
    } finally {
      loading.value = false
    }
  }

  function isAuthenticated() {
    return !!token.value
  }

  return { token, user, loading, error, login, logout, fetchMe, isAuthenticated }
})
