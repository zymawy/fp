import { createApp } from 'vue'
import { createPinia } from 'pinia'
import router from './router'
import axios, { AxiosError } from 'axios'
import { useAuthStore } from './stores/auth'
import './style.css'
import App from './App.vue'

// Get API URL from environment
const apiBaseUrl = import.meta.env.VITE_API_URL

// Configure axios base URL
axios.defaults.baseURL = `${apiBaseUrl}/api`

// Configure axios interceptors
axios.interceptors.request.use(
  config => {
    // Set common headers
    config.headers['Accept'] = 'application/json'
    config.headers['Content-Type'] = 'application/json'
    
    // Get token from localStorage on each request
    // This ensures token is always current even after page refresh
    const token = localStorage.getItem('auth_token')
    if (token) {
      config.headers['Authorization'] = `Bearer ${token}`
    }
    
    return config
  },
  error => {
    return Promise.reject(error)
  }
)

// Response interceptor
axios.interceptors.response.use(
  response => response,
  async (error: AxiosError) => {
    // Don't handle errors during login
    const isLoginRequest = error.config?.url?.includes('/auth/login')
    
    if (error.response?.status === 401 && !isLoginRequest) {
      console.log('Unauthorized access detected, logging out...')
      // Clear auth state on unauthorized
      const authStore = useAuthStore()
      await authStore.clearAuth()
      router.push('/login')
    }
    return Promise.reject(error)
  }
)

const app = createApp(App)

// Install pinia state management
const pinia = createPinia()
app.use(pinia)

// Setup router
app.use(router)

// Initialize auth state - must be after pinia is installed
const authStore = useAuthStore()
authStore.initializeAuth()

app.mount('#app')
