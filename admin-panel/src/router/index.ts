import { createRouter, createWebHistory } from 'vue-router'
import { storeToRefs } from 'pinia'
import { useAuthStore } from '../stores/auth' 

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/login',
      name: 'login',
      component: () => import('../views/auth/LoginView.vue'),
      meta: { requiresGuest: true }
    },
    {
      path: '/',
      component: () => import('../layouts/DashboardLayout.vue'),
      meta: { requiresAuth: true },
      children: [
        {
          path: '',
          name: 'dashboard',
          component: () => import('../views/DashboardView.vue'),
        },
        {
          path: 'users',
          name: 'users',
          component: () => import('../views/users/UsersView.vue'),
        },
        {
          path: 'causes',
          name: 'causes',
          component: () => import('../views/causes/CausesView.vue'),
        },
        {
          path: 'donations',
          name: 'donations',
          component: () => import('../views/donations/DonationsView.vue')
        },
        {
          path: 'partners',
          name: 'partners',
          component: () => import('../views/partners/PartnersView.vue')
        },
        {
          path: 'reports',
          name: 'reports',
          component: () => import('../views/reports/ReportsView.vue')
        },
      ]
    }
  ]
})

router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore()
  const { isAuthenticated } = storeToRefs(authStore)
  
  // Check if token exists but no user data (e.g. after page refresh)
  const hasToken = !!localStorage.getItem('auth_token')
  const hasUserData = !!localStorage.getItem('auth_user')
  
  // Try to restore the session if we have a token
  if (hasToken && !authStore.user && hasUserData) {
    try {
      // Initialize from localStorage (already done in store, but make sure)
      const userData = JSON.parse(localStorage.getItem('auth_user') || 'null')
      if (userData) {
        authStore.setUser(userData)
      }
    } catch (error) {
      console.error('Failed to restore user session:', error)
    }
  }
  
  // Try to fetch user if we think we're authenticated
  if (!to.meta.requiresGuest && isAuthenticated.value) {
    try {
      await authStore.fetchUser()
    } catch (error) {
      console.error('Failed to fetch user:', error)
      authStore.clearAuth()
      return next('/login')
    }
  }

  if (to.meta.requiresAuth && !isAuthenticated.value) {
    next('/login')
  } else if (to.meta.requiresGuest && isAuthenticated.value) {
    next('/')
  } else {
    next()
  }
})

export default router