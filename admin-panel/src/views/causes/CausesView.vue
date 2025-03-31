<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { MagnifyingGlassIcon, FunnelIcon, PencilIcon, TrashIcon } from '@heroicons/vue/24/outline'
import CauseForm from '../../components/causes/CauseForm.vue'
import Spinner from '../../components/ui/Spinner.vue'
import axios from 'axios'
import { useRouter } from 'vue-router'

interface CauseAttributes {
  title: string
  slug: string
  description: string
  image: string | null
  featured_image?: string | null
  goal_amount: number
  raised_amount: number
  status: string
  updated_at: string
  category_id?: string
}

interface Cause {
  id: string
  type: string | null
  attributes: CauseAttributes
}

// Interface that matches what the CauseForm component expects
interface CauseFormData {
  id?: string
  attributes: {
    title: string
    description: string
    goal_amount: number
    category_id: string
    status: string
    image?: string | null
  }
}

interface ErrorResponse {
  response?: {
    status: number;
    statusText: string;
    data: any;
  };
  message?: string;
}

const causes = ref<Cause[]>([])
const loading = ref(true)
const searchQuery = ref('')
const titleFilter = ref('')
const descriptionFilter = ref('')
const minTarget = ref('')
const maxTarget = ref('')
const sortBy = ref('created_at')
const sortDirection = ref('desc')
const page = ref(1)
const perPage = ref(10)
const showCauseForm = ref(false)
const editingCause = ref<CauseFormData | null>(null)
const formError = ref<{ message: string; errors?: Record<string, string[]> } | null>(null)
const meta = ref({
  pagination: {
    total: 0,
    count: 0,
    per_page: perPage.value,
    current_page: 1,
    total_pages: 1
  }
})
const router = useRouter()

const truncateDescription = (text: string, length: number = 200) => {
  if (text.length <= length) return text
  return text.slice(0, length) + '...'
}

const buildQueryParams = (page: number) => {
  const params = new URLSearchParams()
  params.append('page', page.toString())
  params.append('per_page', perPage.value.toString())
  params.append('sort_by', sortBy.value)
  params.append('sort_direction', sortDirection.value)

  if (searchQuery.value) params.append('search', searchQuery.value)
  if (titleFilter.value) params.append('title', titleFilter.value)
  if (descriptionFilter.value) params.append('description', descriptionFilter.value)
  if (minTarget.value) params.append('min_target', minTarget.value)
  if (maxTarget.value) params.append('max_target', maxTarget.value)

  return params.toString()
}

const calculateProgress = (collected: string, target: string) => {
  const collectedAmount = parseFloat(collected) || 0
  const targetAmount = parseFloat(target) || 1 // Prevent division by zero
  return Math.min(Math.round((collectedAmount / targetAmount) * 100), 100)
}

const formatAmount = (amount: number) => {
  const value = amount || 0
  return value.toLocaleString('en-US', {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  })
}

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const toggleSort = (field: string) => {
  if (sortBy.value === field) {
    sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortBy.value = field
    sortDirection.value = 'desc'
  }
  fetchCauses(1)
}

const fetchCauses = async (page: number = 1) => {
  loading.value = true
  try {
    const baseUrl = import.meta.env.VITE_API_URL
    const params = buildQueryParams(page)
    const response = await axios.get(`${baseUrl}/api/causes${params ? `?${params}` : ''}`)
    causes.value = response.data.data
    meta.value = response.data.meta
  } catch (error: any) {
    console.error('Failed to fetch causes:', {
      message: error?.message,
      response: error?.response ? {
        status: error.response.status,
        statusText: error.response.statusText,
        data: error.response.data
      } : 'No response'
    })
    causes.value = []
  } finally {
    loading.value = false
  }
}

const clearFilters = () => {
  searchQuery.value = ''
  titleFilter.value = ''
  descriptionFilter.value = ''
  minTarget.value = ''
  maxTarget.value = ''
  page.value = 1
  fetchCauses(1)
}

const handleAddCause = async (formData: FormData) => {
  try {
    formError.value = null
    const baseUrl = import.meta.env.VITE_API_URL

    // Check if this contains a file
    const hasFile = Array.from(formData.entries()).some(([key, value]) => value instanceof File)

	  console.log('hasFile', hasFile)
    if (hasFile) {
      console.log('FormData contains file - using multipart/form-data')

      // FormData with files: Don't manually set Content-Type
      // Let the browser set it with the correct boundary
      const response = await axios.post(
        `${baseUrl}/api/causes`,
        formData
      )

      console.log('FormData upload successful:', response.data)
    } else {
      console.log('FormData has no files - converting to JSON')

      // No file upload: convert to regular JSON
      const jsonData = Object.fromEntries(formData.entries())
      delete jsonData.featured_image // Remove empty featured_image if it exists

      await axios.post(`${baseUrl}/api/causes`, jsonData, {
        headers: {
          'Content-Type': 'application/json'
        }
      })
    }

    showCauseForm.value = false
    fetchCauses(page.value)
  } catch (error: any) {
    console.error('Error response:', error.response?.data);
    if (error.response?.status === 422) {
      formError.value = {
        message: error.response.data.message,
        errors: error.response.data.errors
      }
    } else {
      formError.value = { message: 'An unexpected error occurred. Please try again.' }
      console.error('Failed to add cause:', error)
    }
  }
}

const handleEditCause = async (formData: FormData) => {
  try {
    formError.value = null
    const baseUrl = import.meta.env.VITE_API_URL

    // Get ID from FormData
    const causeId = formData.get('id')?.toString() || ''
    console.log('Updating cause ID:', causeId);

    // Check if this contains a file
    const hasFile = Array.from(formData.entries()).some(([key, value]) => value instanceof File)

    if (hasFile) {
      console.log('Edit FormData contains file - using multipart/form-data')

      // Method spoofing for Laravel
      formData.append('_method', 'PUT');

      // FormData with files: Don't manually set Content-Type
      // Let the browser set it with the correct boundary
      const response = await axios.post(
        `${baseUrl}/api/causes/${causeId}`,
        formData,
        {
          headers: {
            // Remove content-type so browser sets it with boundary
            // 'Content-Type': 'multipart/form-data'
          }
        }
      )

      console.log('FormData update successful:', response.data)
    } else {
      console.log('Edit FormData has no files - converting to JSON')

      // No file upload: convert to regular JSON and use PUT
      const jsonData = Object.fromEntries(formData.entries())
      delete jsonData.featured_image // Remove empty featured_image if it exists

      await axios.put(`${baseUrl}/api/causes/${causeId}`, jsonData, {
        headers: {
          'Content-Type': 'application/json'
        }
      })
    }

    showCauseForm.value = false
    editingCause.value = null
    fetchCauses(page.value)
  } catch (error: any) {
    console.error('Error response:', error.response?.data);
    if (error.response?.status === 422) {
      formError.value = {
        message: error.response.data.message,
        errors: error.response.data.errors
      }
    } else {
      formError.value = { message: 'An unexpected error occurred. Please try again.' }
      console.error('Failed to update cause:', error)
    }
  }
}

const handleDeleteCause = async (causeId: string) => {
  if (!confirm('Are you sure you want to delete this cause?')) return

  try {
    const baseUrl = import.meta.env.VITE_API_URL
    await axios.delete(`${baseUrl}/api/causes/${causeId}`)
    fetchCauses(page.value)
  } catch (error: any) {
    console.error('Failed to delete cause:', {
      message: error?.message,
      response: error?.response ? {
        status: error.response.status,
        statusText: error.response.statusText,
        data: error.response.data
      } : 'No response'
    })
    
    // Show error message to admin
    let errorMessage = 'Failed to delete cause. Please try again later.'
    
    if (error.response?.data?.message) {
      errorMessage = error.response.data.message
    } else if (error.response?.data?.error) {
      errorMessage = error.response.data.error
    } else if (error.message) {
      errorMessage = error.message
    }
    
    alert(errorMessage)
  }
}

const openEditForm = (cause: Cause) => {
  // Transform the cause format to match what CauseForm expects
  editingCause.value = {
    id: cause.id,
    attributes: {
      title: cause.attributes.title,
      description: cause.attributes.description,
      goal_amount: cause.attributes.goal_amount,
      category_id: cause.attributes.category_id || '',
      status: cause.attributes.status,
      image: cause.attributes.image
    }
  }
  showCauseForm.value = true
}

onMounted(() => {
  fetchCauses()
})
</script>

<template>
  <div class="space-y-6">
    <div class="sm:flex sm:items-center sm:justify-between">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">Causes</h1>
        <p class="mt-1 text-sm text-gray-500">Manage and monitor fundraising causes</p>
      </div>
      <div class="mt-4 sm:mt-0">
        <button
          type="button"
          class="inline-flex items-center rounded-md bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
          @click="showCauseForm = true"
        >
          Add Cause
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
                placeholder="Search causes..."
                @input="fetchCauses(1)"
              />
            </div>
          </div>

          <!-- Category filter -->
          <div class="sm:w-48">
            <input
              v-model="titleFilter"
              type="text"
              class="block w-full rounded-md border-0 py-2.5 pl-3 pr-3 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6"
              placeholder="Filter by title..."
              @input="fetchCauses(1)"
            />
          </div>

          <div class="sm:w-48">
            <input
              v-model="descriptionFilter"
              type="text"
              class="block w-full rounded-md border-0 py-2.5 pl-3 pr-3 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6"
              placeholder="Filter by description..."
              @input="fetchCauses(1)"
            />
          </div>

          <div class="flex gap-2">
            <input
              v-model="minTarget"
              type="number"
              class="block w-24 rounded-md border-0 py-2.5 pl-3 pr-3 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6"
              placeholder="Min $"
              @input="fetchCauses(1)"
            />
            <input
              v-model="maxTarget"
              type="number"
              class="block w-24 rounded-md border-0 py-2.5 pl-3 pr-3 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6"
              placeholder="Max $"
              @input="fetchCauses(1)"
            />
          </div>

          <!-- Clear filters -->
          <button
            v-if="searchQuery || titleFilter || descriptionFilter || minTarget || maxTarget"
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
    <div class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl overflow-hidden">
      <div class="min-h-[536px]">
        <table class="min-w-full divide-y divide-gray-300">
          <thead>
            <tr>
              <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">
                <button
                  class="group inline-flex"
                  @click="toggleSort('created_at')"
                >
                  Title
                  <span class="ml-2 flex-none rounded text-gray-400">
                    {{ sortBy === 'created_at' ? (sortDirection === 'desc' ? '↓' : '↑') : '' }}
                  </span>
                </button>
              </th>
              <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Status</th>
              <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                <button
                  class="group inline-flex"
                  @click="toggleSort('goal_amount')"
                >
                  Target
                  <span class="ml-2 flex-none rounded text-gray-400">
                    {{ sortBy === 'goal_amount' ? (sortDirection === 'desc' ? '↓' : '↑') : '' }}
                  </span>
                </button>
              </th>
              <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Progress</th>
              <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                <span class="sr-only">Actions</span>
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 bg-white">
            <tr v-if="loading">
              <td colspan="8" class="px-3 py-12 text-sm text-gray-500">
                <div class="flex flex-col items-center justify-center gap-3">
                  <Spinner size="md" color="primary" />
                  <p class="text-sm text-gray-500">Loading causes...</p>
                </div>
              </td>
            </tr>
            <tr v-else-if="causes.length === 0">
              <td colspan="8" class="px-3 py-4 text-sm text-gray-500 text-center">
                {{ searchQuery || titleFilter || descriptionFilter || minTarget || maxTarget ? 'No causes match the current filters' : 'No causes found' }}
              </td>
            </tr>
            <tr v-else v-for="cause in causes" :key="cause.id" class="hover:bg-gray-50">
              <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm sm:pl-6">
                <div class="flex items-center">
                  <img
                    :src="cause.attributes.featured_image || cause.attributes.image || 'https://source.unsplash.com/random/800x600/?charity'"
                    :alt="cause.attributes.title"
                    class="h-10 w-10 flex-shrink-0 rounded-full object-cover"
                  />
                  <div class="ml-4">
                    <div class="font-medium text-gray-900">{{ cause.attributes.title }}</div>
                    <div class="text-gray-500 line-clamp-2 max-w-md">{{ truncateDescription(cause.attributes.description) }}</div>
                  </div>
                </div>
              </td>
              <td class="whitespace-nowrap px-3 py-4 text-sm">
                <span :class="{
                  'inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset': true,
                  'bg-green-50 text-green-700 ring-green-600/20': cause.attributes.status === 'active',
                  'bg-yellow-50 text-yellow-700 ring-yellow-600/20': cause.attributes.status === 'inactive',
                  'bg-gray-50 text-gray-700 ring-gray-600/20': cause.attributes.status === 'completed'
                }">
                  {{ cause.attributes.status ? cause.attributes.status.charAt(0).toUpperCase() + cause.attributes.status.slice(1) : 'Unknown' }}
                </span>
              </td>
              <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900">
                {{ formatAmount(cause.attributes.goal_amount) }}
              </td>
              <td class="px-3 py-4 text-sm text-gray-500">
                <div class="flex items-center gap-2">
                  <div class="flex-1 h-2 bg-gray-200 rounded-full w-32">
                    <div
                      class="bg-primary-600 h-2 rounded-full"
                      :style="`width: ${calculateProgress(String(cause.attributes.raised_amount), String(cause.attributes.goal_amount))}%`"
                    ></div>
                  </div>
                  <span class="text-gray-900 font-medium whitespace-nowrap">
                    {{ calculateProgress(String(cause.attributes.raised_amount), String(cause.attributes.goal_amount)) }}%
                  </span>
                </div>
                <div class="mt-1 text-xs">
                  {{ formatAmount(cause.attributes.raised_amount) }} raised
                </div>
              </td>
              <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                <div class="flex items-center justify-end gap-2">
                  <button
                    type="button"
                    class="text-primary-600 hover:text-primary-900"
                    @click="openEditForm(cause)"
                  >
                    <PencilIcon class="h-5 w-5" />
                    <span class="sr-only">Edit</span>
                  </button>
                  <button
                    type="button"
                    class="text-red-600 hover:text-red-900"
                    @click="handleDeleteCause(cause.id)"
                  >
                    <TrashIcon class="h-5 w-5" />
                    <span class="sr-only">Delete</span>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      </div>

    <!-- Pagination -->
    <div class="flex items-center justify-between border-t border-gray-200 bg-white px-4 py-3 sm:px-6">
      <div class="flex flex-1 justify-between sm:hidden">
        <button
          :disabled="page === 1"
          @click="fetchCauses(page - 1)"
          class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
          :class="{ 'opacity-50 cursor-not-allowed': page === 1 }"
        >
          Previous
        </button>
        <button
          :disabled="page === meta.pagination.total_pages"
          @click="fetchCauses(page + 1)"
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
              @click="fetchCauses(page - 1)"
              class="relative inline-flex items-center rounded-l-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0"
              :class="{ 'opacity-50 cursor-not-allowed': page === 1 }"
            >
              Previous
            </button>
            <button
              v-for="pageNum in meta.pagination.total_pages"
              :key="pageNum"
              @click="fetchCauses(pageNum)"
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
              @click="fetchCauses(page + 1)"
              class="relative inline-flex items-center rounded-r-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0"
              :class="{ 'opacity-50 cursor-not-allowed': page === meta.pagination.total_pages }"
            >
              Next
            </button>
          </nav>
        </div>
      </div>
    </div>

    <!-- Cause Form Modal -->
    <CauseForm
      v-if="showCauseForm"
      :edit-mode="!!editingCause"
      :initial-data="editingCause || undefined"
      @close="showCauseForm = false; editingCause = null"
      :error="formError"
      @submit="editingCause ? handleEditCause($event) : handleAddCause($event)"
    />
  </div>
</template>
