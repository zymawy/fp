<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { MagnifyingGlassIcon, FunnelIcon } from '@heroicons/vue/24/outline'
import UserForm from '../../components/users/UserForm.vue'
import Spinner from '../../components/ui/Spinner.vue'
import axios from 'axios'

interface UserAttributes {
  name: string
  email: string
  role: string
  created_at: string
}

interface User {
  id: string
  type: string | null
  attributes: UserAttributes
}

const users = ref<User[]>([])
const meta = ref({
  pagination: {
    total: 0,
    count: 0,
    per_page: 10,
    current_page: 1,
    total_pages: 1
  }
})

const loading = ref(true)
const searchQuery = ref('')
const selectedRole = ref('')
const page = ref(1)
const perPage = ref(10)
const showAddUserForm = ref(false)

const roles = ['admin', 'user', 'partner']

const buildQueryParams = (page: number) => {
  const params = new URLSearchParams()
  params.append('page', page.toString())
  params.append('per_page', perPage.value.toString())

  if (searchQuery.value) params.append('search', searchQuery.value)
  if (selectedRole.value) params.append('role', selectedRole.value)

  return params.toString()
}

const fetchUsers = async (pageNum: number = 1) => {
  loading.value = true
  try {
    const baseUrl = import.meta.env.VITE_API_URL
    const params = buildQueryParams(pageNum)
    const response = await axios.get(`${baseUrl}/api/users${params ? `?${params}` : ''}`)
    users.value = response.data.data
    meta.value = response.data.meta
  } catch (error) {
    console.error('Failed to fetch users:', error)
    users.value = []
  } finally {
    loading.value = false
  }
}

const clearFilters = () => {
  searchQuery.value = ''
  selectedRole.value = ''
  page.value = 1
  fetchUsers(1)
}

const handleAddUser = async (userData: any) => {
  try {
    const baseUrl = import.meta.env.VITE_API_URL
    await axios.post(`${baseUrl}/api/users`, userData)
    showAddUserForm.value = false
    fetchUsers(page.value)
  } catch (error) {
    console.error('Failed to add user:', error)
  }
}

onMounted(async () => {
  fetchUsers()
})
</script>

<template>
  <div class="space-y-6">
    <div class="sm:flex sm:items-center sm:justify-between">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">Users</h1>
        <p class="mt-1 text-sm text-gray-500">Manage and monitor user accounts</p>
      </div>
      <div class="mt-4 sm:mt-0">
        <button
          type="button"
          class="inline-flex items-center rounded-md bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
          @click="showAddUserForm = true"
        >
          Add User
        </button>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl">
      <div class="p-6">
        <div class="flex flex-col sm:flex-row gap-4">
          <!-- Search -->
          <div class="flex-1">
            <div class="relative">
              <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                <MagnifyingGlassIcon class="h-5 w-5 text-gray-400" />
              </div>
              <input
                v-model="searchQuery"
                type="text"
                class="block w-full rounded-md border-0 py-2.5 pl-10 pr-3 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6"
                placeholder="Search users..."
                @input="fetchUsers(1)"
              />
            </div>
          </div>

          <!-- Role filter -->
          <div class="sm:w-48">
            <select
              v-model="selectedRole"
              class="block w-full rounded-md border-0 py-2.5 pl-3 pr-10 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-primary-600 sm:text-sm sm:leading-6"
              @change="fetchUsers(1)"
            >
              <option value="">All roles</option>
              <option v-for="role in roles" :key="role" :value="role">
                {{ role.charAt(0).toUpperCase() + role.slice(1) }}
              </option>
            </select>
          </div>

          <!-- Clear filters -->
          <button
            v-if="searchQuery || selectedRole"
            @click="clearFilters"
            type="button"
            class="inline-flex items-center rounded-md bg-white px-4 py-2.5 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50"
          >
            <FunnelIcon class="h-5 w-5 mr-1.5 text-gray-400" />
            Clear filters
          </button>
        </div>
      </div>
    </div>

    <!-- Table -->
    <div class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl">
      <div class="min-h-[536px]"> <!-- Fixed height to prevent layout shift -->
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-300">
            <thead>
              <tr>
                <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Name</th>
                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Email</th>
                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Role</th>
                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Joined</th>
                <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                  <span class="sr-only">Actions</span>
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-if="loading">
                <td colspan="5" class="px-3 py-12 text-sm text-gray-500">
                  <div class="flex flex-col items-center justify-center gap-3">
                    <Spinner size="md" color="primary" />
                    <p class="text-sm text-gray-500">Loading users...</p>
                  </div>
                </td>
              </tr>
              <tr v-else-if="users.length === 0">
                <td colspan="5" class="px-3 py-4 text-sm text-gray-500 text-center">
                  {{ searchQuery || selectedRole ? 'No users match the current filters' : 'No users found' }}
                </td>
              </tr>
              <tr v-else v-for="user in users" :key="user.id" class="hover:bg-gray-50">
                <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">
                  {{ user.attributes.name }}
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ user.attributes.email }}</td>
                <td class="whitespace-nowrap px-3 py-4 text-sm">
                  <span :class="{
                    'inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset': true,
                    'bg-primary-50 text-primary-700 ring-primary-600/20': user.attributes.role === 'admin',
                    'bg-green-50 text-green-700 ring-green-600/20': user.attributes.role === 'user',
                    'bg-blue-50 text-blue-700 ring-blue-600/20': user.attributes.role === 'partner',
                    'bg-gray-50 text-gray-700 ring-gray-600/20': !user.attributes.role
                  }">
                    {{ user.attributes.role ? (user.attributes.role.charAt(0).toUpperCase() + user.attributes.role.slice(1)) : 'Not assigned' }}
                  </span>
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                  {{ new Date(user.attributes.created_at).toLocaleDateString() }}
                </td>
                <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                  <button
                    type="button"
                    class="text-primary-600 hover:text-primary-900 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 rounded-md px-2 py-1"
                  >
                    Edit
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Pagination -->
      <div class="flex items-center justify-between border-t border-gray-200 px-4 py-3 sm:px-6">
        <div class="flex flex-1 justify-between sm:hidden">
          <button
            :disabled="page === 1"
            @click="fetchUsers(page - 1)"
            class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
            :class="{ 'opacity-50 cursor-not-allowed': page === 1 }"
          >
            Previous
          </button>
          <button
            :disabled="page === meta.pagination.total_pages"
            @click="fetchUsers(page + 1)"
            class="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
            :class="{ 'opacity-50 cursor-not-allowed': page === meta.pagination.total_pages }"
          >
            Next
          </button>
        </div>
        <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
          <div>
            <p class="text-sm text-gray-700">
              Showing
              <span class="font-medium">{{ ((page - 1) * meta.pagination.per_page) + 1 }}</span>
              to
              <span class="font-medium">{{ Math.min(page * meta.pagination.per_page, meta.pagination.total) }}</span>
              of
              <span class="font-medium">{{ meta.pagination.total }}</span>
              results
            </p>
          </div>
          <div>
            <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
              <button
                :disabled="page === 1"
                @click="fetchUsers(page - 1)"
                class="relative inline-flex items-center rounded-l-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0"
                :class="{ 'opacity-50 cursor-not-allowed': page === 1 }"
              >
                Previous
              </button>
              <button
                v-for="pageNum in meta.pagination.total_pages"
                :key="pageNum"
                @click="fetchUsers(pageNum)"
                :class="[
                  pageNum === page
                    ? 'relative z-10 inline-flex items-center bg-primary-600 px-4 py-2 text-sm font-semibold text-white focus:z-20 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600'
                    : 'relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0',
                ]"
              >
                {{ pageNum }}
              </button>
              <button
                :disabled="page === meta.pagination.total_pages"
                @click="fetchUsers(page + 1)"
                class="relative inline-flex items-center rounded-r-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0"
                :class="{ 'opacity-50 cursor-not-allowed': page === meta.pagination.total_pages }"
              >
                Next
              </button>
            </nav>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Add User Form Modal -->
    <UserForm
      v-if="showAddUserForm"
      @close="showAddUserForm = false"
      @submit="handleAddUser"
    />
  </div>
</template>