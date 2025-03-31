<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { storeToRefs } from 'pinia'
import NavigationProgress from '../components/ui/NavigationProgress.vue'
import { useAuthStore } from '../stores/auth' 
import {
  Bars3Icon, XMarkIcon, HomeIcon, UsersIcon, HeartIcon,
  CurrencyDollarIcon, BuildingLibraryIcon, ChartBarIcon,
  BellIcon
} from '@heroicons/vue/24/outline'

const navigation = [
  { name: 'Dashboard', to: '/', icon: HomeIcon },
  { name: 'Users', to: '/users', icon: UsersIcon },
  { name: 'Causes', to: '/causes', icon: HeartIcon },
  { name: 'Donations', to: '/donations', icon: CurrencyDollarIcon },
  { name: 'Partners', to: '/partners', icon: BuildingLibraryIcon },
  { name: 'Reports', to: '/reports', icon: ChartBarIcon }
]

const mobileMenuOpen = ref(false)
const router = useRouter()
const authStore = useAuthStore()
const { user } = storeToRefs(authStore)

const logout = async () => {
  await authStore.logout()
  router.push('/login')
}
</script>

<template>
  <div>
    <NavigationProgress />
    
    <!-- Mobile menu -->
    <div v-show="mobileMenuOpen" class="relative z-50 lg:hidden">
      <div class="fixed inset-0 bg-gray-900/80"></div>
      <div class="fixed inset-y-0 right-0 z-50 w-full overflow-y-auto bg-white px-6 py-6 sm:max-w-sm sm:ring-1 sm:ring-gray-900/10">
        <div class="flex items-center justify-between">
          <img class="h-8 w-auto" src="/logo.svg" alt="Fundraiser" />
          <button type="button" @click="mobileMenuOpen = false" class="-m-2.5 rounded-md p-2.5 text-gray-700">
            <XMarkIcon class="h-6 w-6" />
          </button>
        </div>
        <div class="mt-6 flow-root">
          <div class="-my-6 divide-y divide-gray-500/10">
            <div class="space-y-2 py-6">
              <router-link
                v-for="item in navigation"
                :key="item.name"
                :to="item.to"
                :class="[$route.path === item.to ? 'bg-primary-50 text-primary-600' : 'text-gray-700 hover:text-primary-600 hover:bg-primary-50']"
                class="group flex items-center rounded-lg px-3 py-2 text-base font-semibold leading-7"
                @click="mobileMenuOpen = false"
              >
                <component :is="item.icon" class="mr-3 h-6 w-6 shrink-0" />
                {{ item.name }}
              </router-link>
            </div>
            <div class="py-6">
              <button
                @click="logout"
                class="w-full text-left rounded-lg px-3 py-2 text-base font-semibold leading-7 text-gray-900 hover:bg-gray-50"
              >
                Logout
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Desktop navigation -->
    <nav class="bg-white shadow">
      <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 justify-between">
          <!-- Logo -->
          <div class="flex">
            <div class="flex flex-shrink-0 items-center">
              <img class="h-8 w-auto" src="/logo.svg" alt="Fundraiser" />
            </div>
          </div>

          <!-- Desktop menu -->
          <div class="hidden lg:ml-6 lg:flex lg:space-x-8">
            <router-link
              v-for="item in navigation"
              :key="item.name"
              :to="item.to"
              :class="[$route.path === item.to ? 'border-primary-600 text-primary-600' : 'border-transparent text-gray-500 hover:border-primary-200 hover:text-primary-600']"
              class="inline-flex items-center border-b-2 px-1 pt-1 text-sm font-medium"
            >
              <component :is="item.icon" class="mr-2 h-5 w-5" />
              {{ item.name }}
            </router-link>
          </div>
          <!-- Mobile menu button -->
          <div class="flex items-center lg:hidden">
            <button
              type="button"
              @click="mobileMenuOpen = true"
              class="-m-2.5 inline-flex items-center justify-center rounded-md p-2.5 text-gray-700"
            >
              <Bars3Icon class="h-6 w-6" />
            </button>
          </div>
          <!-- User menu -->
          <div class="hidden lg:ml-4 lg:flex lg:items-center">
            <button type="button" class="relative rounded-full bg-white p-1 text-gray-400 hover:text-gray-500">
              <BellIcon class="h-6 w-6" />
            </button>
            <div class="ml-4 flex items-center space-x-3">
              <div class="hidden lg:block">
                <div class="flex items-center">
                  <img
                    v-if="user?.avatar_url"
                    :src="user.avatar_url"
                    :alt="user?.name"
                    class="h-8 w-8 rounded-full mr-2"
                  />
                  <div class="flex flex-col">
                    <span class="text-sm font-medium text-gray-900">{{ user?.name }}</span>
                    <span class="text-xs text-gray-500">{{ user?.email }}</span>
                  </div>
                </div>
              </div>
              <button
                @click="logout"
                class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50"
              >
                Logout
              </button>
            </div>
          </div>
        </div>
      </div>
    </nav>

    <!-- Main content -->
    <main class="mx-auto max-w-7xl py-6 sm:px-6 lg:px-8">
      <router-view></router-view>
    </main>
  </div>
</template>