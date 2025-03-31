import { defineStore } from 'pinia'
import axios from 'axios'

// Set base URL from environment variable
const baseURL = import.meta.env.VITE_API_URL 
axios.defaults.baseURL = `${baseURL}/api`

interface User {
  id: number
  name: string
  email: string
  role: string
}

interface LoginCredentials {
  email: string
  password: string
}

interface AuthState {
  user: User | null
  token: string | null
  loading: boolean
  error: string | null
}

export const useAuthStore = defineStore('auth', {
  state: (): AuthState => ({
    user: JSON.parse(localStorage.getItem('auth_user') || 'null'),
    token: localStorage.getItem('auth_token'),
    loading: false,
    error: null,
  }),
  
  getters: {
    isAuthenticated: (state) => !!state.token,
    isAdmin: (state) => state.user?.role === 'admin',
  },
  
  actions: {
    async login(credentials: LoginCredentials) {
      this.loading = true
      this.error = null
      
      try {
        const response = await axios.post('/auth/login', credentials)
        
        // Handle the nested response structure
        if (response.data.status === 'success' || response.data.success) {
          const { user, access_token, token } = response.data.data || response.data
          
          this.setToken(access_token || token)
          this.setUser(user)
          return true
        } else {
          throw new Error(response.data.message || 'Login failed')
        }
      } catch (error: any) {
        this.error = error.response?.data?.message || error.message || 'Login failed'
        return false
      } finally {
        this.loading = false
      }
    },
    
    async logout() {
      this.loading = true
      
      try {
        if (this.token) {
          await axios.post('/auth/logout')
        }
      } catch (error) {
        // Ignore logout errors, just clear the state
      } finally {
        this.clearAuth()
        this.loading = false
      }
    },
    
    async fetchUser() {
      if (!this.token) return null
      
      this.loading = true
      
      try {
        const response = await axios.get('/user')
        
        // Handle the potentially nested response structure
        const userData = response.data.data?.user || response.data.data || response.data
        this.setUser(userData)
        return userData
      } catch (error: any) {
        console.error('Error fetching user:', error)
        if (error.response?.status === 401) {
          this.clearAuth()
        }
        return null
      } finally {
        this.loading = false
      }
    },
    
    setToken(token: string) {
      this.token = token
      localStorage.setItem('auth_token', token)
      // Set token for all future axios requests
      axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
    },
    
    setUser(user: User) {
      this.user = user
      // Store user in localStorage to persist across page refreshes
      localStorage.setItem('auth_user', JSON.stringify(user))
    },
    
    clearAuth() {
      this.user = null
      this.token = null
      localStorage.removeItem('auth_token')
      localStorage.removeItem('auth_user')
      // Remove authorization header
      delete axios.defaults.headers.common['Authorization']
    },
    
    initializeAuth() {
      // Get auth data from localStorage
      const token = localStorage.getItem('auth_token')
      const userJson = localStorage.getItem('auth_user')
      
      if (token) {
        this.token = token
        // Set authorization header for all future requests
        axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
        
        // Initialize user from localStorage if available
        if (userJson) {
          try {
            this.user = JSON.parse(userJson)
          } catch (error) {
            console.error('Error parsing stored user data:', error)
          }
        }
        
        // Always verify the token by fetching fresh user data
        this.fetchUser().catch(error => {
          console.error('Failed to refresh user data:', error)
        })
      }
    }
  }
}) 