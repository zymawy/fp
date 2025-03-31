<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import { ExclamationCircleIcon } from '@heroicons/vue/24/outline'

const email = ref('')
const password = ref('')
const error = ref('')
const loading = ref(false)
const rememberMe = ref(false)

const router = useRouter()
const authStore = useAuthStore()

const isValidEmail = computed(() => {
  return !email.value || /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)
})

const isValidPassword = computed(() => {
  return !password.value || password.value.length >= 6
})

const handleSubmit = async () => {
  if (!email.value || !password.value) {
    error.value = 'Please fill in all fields'
    return
  }

  if (!isValidEmail.value) {
    error.value = 'Please enter a valid email address'
    return
  }

  if (!isValidPassword.value) {
    error.value = 'Password must be at least 6 characters'
    return
  }

  error.value = ''
  loading.value = true

  try { 
    const success = await authStore.login({
      email: email.value,
      password: password.value
    })
    
    if (success) {
      router.push('/')
    } else {
      error.value = authStore.error || 'Login failed. Please try again.'
    }
  } catch (e) {
    const err = e as any
    console.error('Login error details:', {
      status: err.response?.status,
      message: err.response?.data?.message,
      error: err.response?.data?.error,
      errors: err.response?.data?.errors,
      data: err.response?.data
    })

    if (err.response?.status === 401) {
      error.value = 'Invalid email or password'
    } else if (err.response?.data?.message) {
      error.value = err.response.data.message
    } else if (err.response?.data?.error) {
      error.value = err.response.data.error
    } else if (err.response?.data?.errors) {
      // Handle validation errors
      const firstError = Object.values(err.response.data.errors)[0]
      error.value = Array.isArray(firstError) ? firstError[0] : firstError
    } else if (err.message === 'Network Error') {
      error.value = 'Unable to connect to the server. Please check your internet connection.'
    } else {
      error.value = 'An unexpected error occurred. Please try again.'
    }
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  // Only clear auth state if we're not already authenticated
  if (!authStore.isAuthenticated) {
    authStore.clearAuth()
  }
})
</script>

<template>
  <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
      <div>
        <img class="mx-auto h-12 w-auto" src="/logo.svg" alt="Logo" />
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
          Welcome Back
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600">
          Sign in to access your dashboard
        </p>
      </div>
      <form class="mt-8 space-y-4" @submit.prevent="handleSubmit">
        <div class="space-y-4">
          <div class="relative">
            <label for="email-address" class="sr-only">Email address</label>
            <input
              id="email-address"
              name="email"
              type="email"
              required
              autocomplete="email"
              v-model="email"
              :class="[
                'block w-full rounded-md border-0 py-2.5 px-3 text-gray-900 shadow-sm ring-1 ring-inset placeholder:text-gray-400 focus:ring-2 focus:ring-inset sm:text-sm sm:leading-6',
                !isValidEmail && email
                  ? 'ring-red-300 focus:ring-red-500'
                  : 'ring-gray-300 focus:ring-primary-500'
              ]"
              placeholder="Email address"
            />
            <div v-if="!isValidEmail && email" class="absolute inset-y-0 right-0 flex items-center pr-3">
              <ExclamationCircleIcon class="h-5 w-5 text-red-500" />
            </div>
          </div>
          <div class="relative">
            <label for="password" class="sr-only">Password</label>
            <input
              id="password"
              name="password"
              type="password"
              required
              autocomplete="current-password"
              v-model="password"
              :class="[
                'block w-full rounded-md border-0 py-2.5 px-3 text-gray-900 shadow-sm ring-1 ring-inset placeholder:text-gray-400 focus:ring-2 focus:ring-inset sm:text-sm sm:leading-6',
                !isValidPassword && password
                  ? 'ring-red-300 focus:ring-red-500'
                  : 'ring-gray-300 focus:ring-primary-500'
              ]"
              placeholder="Password"
            />
            <div v-if="!isValidPassword && password" class="absolute inset-y-0 right-0 flex items-center pr-3">
              <ExclamationCircleIcon class="h-5 w-5 text-red-500" />
            </div>
          </div>
        </div>

        <div class="flex items-center justify-between">
          <div class="flex items-center">
            <input
              id="remember-me"
              name="remember-me"
              type="checkbox"
              v-model="rememberMe"
              class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-600"
            />
            <label for="remember-me" class="ml-2 block text-sm text-gray-900">Remember me</label>
          </div>

          <div class="text-sm">
            <a href="#" class="font-medium text-primary-600 hover:text-primary-500">
              Forgot your password?
            </a>
          </div>
        </div>

        <div v-if="error" class="text-red-600 text-sm text-center">
          {{ error }}
        </div>

        <div>
          <button
            type="submit"
            :disabled="loading"
            class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
            :class="{ 'opacity-75 cursor-not-allowed': loading || (!isValidEmail && email) || (!isValidPassword && password) }"
          >
            {{ loading ? 'Signing in...' : 'Sign in' }}
          </button>
        </div>
        <p class="mt-2 text-center text-sm text-gray-600">
          Need an account?
          <a href="#" class="font-medium text-primary-600 hover:text-primary-500">
            Sign up
          </a>
        </p>
      </form>
    </div>
  </div>
</template>