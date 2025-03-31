<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { MagnifyingGlassIcon, FunnelIcon, PencilIcon, TrashIcon } from '@heroicons/vue/24/outline'
import ConfirmDialog from '../../components/ui/ConfirmDialog.vue'
import Spinner from '../../components/ui/Spinner.vue'
import PartnerForm from '../../components/partners/PartnerForm.vue'
import axios from 'axios'

interface PartnerAttributes {
  name: string
  logo: string | null
  description: string | null
  website: string
  is_featured: boolean
  status?: string
  created_at: string
  updated_at: string
  deleted_at: string | null
}

interface PartnerRelationships {
  causes: any[]
}

interface Partner {
  id: string
  type: string
  attributes: PartnerAttributes
  relationships: PartnerRelationships
}

interface PartnerFormData {
  id?: string
  attributes: {
    name: string
    website: string
    description: string | null
    logo?: string | null
    is_featured: boolean
    status?: string
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

const partners = ref<Partner[]>([])
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
const selectedStatus = ref('')
const page = ref(1)
const perPage = ref(10)
const showDeleteConfirm = ref(false)
const partnerToDelete = ref<string | null>(null)
const showPartnerForm = ref(false)
const editingPartner = ref<PartnerFormData | null>(null)
const formError = ref<{ message: string; errors?: Record<string, string[]> } | null>(null)

const statuses = ['active', 'pending', 'inactive']

const buildQueryParams = (page: number) => {
  const params = new URLSearchParams()
  params.append('page', page.toString())
  params.append('per_page', perPage.value.toString())

  if (searchQuery.value) params.append('search', searchQuery.value)
  if (selectedStatus.value) params.append('status', selectedStatus.value)

  return params.toString()
}

const fetchPartners = async (pageNum: number = 1) => {
  loading.value = true
  try {
    const baseUrl = import.meta.env.VITE_API_URL
    const params = buildQueryParams(pageNum)
    const response = await axios.get(`${baseUrl}/api/partners${params ? `?${params}` : ''}`)
    partners.value = response.data.data
    meta.value = response.data.meta
    page.value = pageNum
  } catch (error: any) {
    console.error('Failed to fetch partners:', {
      message: error?.message,
      response: error?.response ? {
        status: error.response.status,
        statusText: error.response.statusText,
        data: error.response.data
      } : 'No response'
    })
    partners.value = []
  } finally {
    loading.value = false
  }
}

const clearFilters = () => {
  searchQuery.value = ''
  selectedStatus.value = ''
  page.value = 1
  fetchPartners(1)
}

const confirmDelete = (partnerId: string) => {
  partnerToDelete.value = partnerId
  showDeleteConfirm.value = true
}

const handleDeletePartner = async () => {
  if (!partnerToDelete.value) return

  try {
    const baseUrl = import.meta.env.VITE_API_URL
    await axios.delete(`${baseUrl}/api/partners/${partnerToDelete.value}`)
    showDeleteConfirm.value = false
    partnerToDelete.value = null
    fetchPartners(page.value)
  } catch (error: any) {
    console.error('Failed to delete partner:', {
      message: error?.message,
      response: error?.response ? {
        status: error.response.status,
        statusText: error.response.statusText,
        data: error.response.data
      } : 'No response'
    })
    
    // Show error message to admin
    let errorMessage = 'Failed to delete partner. Please try again later.'
    
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

const handleAddPartner = async (formData: FormData) => {
  try {
    formError.value = null
    const baseUrl = import.meta.env.VITE_API_URL

    // Check if this contains a file
    const hasFile = Array.from(formData.entries()).some(([key, value]) => value instanceof File && value.size > 0)

    if (hasFile) {
      console.log('FormData contains file - using multipart/form-data')

      // FormData with files: Don't manually set Content-Type
      // Let the browser set it with the correct boundary
      const response = await axios.post(
        `${baseUrl}/api/partners`,
        formData
      )

      console.log('FormData upload successful:', response.data)
    } else {
      console.log('FormData has no files - converting to JSON')

      // No file upload: convert to regular JSON
      const jsonData = Object.fromEntries(formData.entries())
      delete jsonData.logo // Remove empty logo if it exists

      await axios.post(`${baseUrl}/api/partners`, jsonData, {
        headers: {
          'Content-Type': 'application/json'
        }
      })
    }

    showPartnerForm.value = false
    fetchPartners(page.value)
  } catch (error: any) {
    console.error('Error response:', error.response?.data);
    if (error.response?.status === 422) {
      formError.value = {
        message: error.response.data.message,
        errors: error.response.data.errors
      }
    } else {
      formError.value = { message: 'An unexpected error occurred. Please try again.' }
      console.error('Failed to add partner:', error)
    }
  }
}

const handleEditPartner = async (formData: FormData) => {
  try {
    formError.value = null
    const baseUrl = import.meta.env.VITE_API_URL

    // Get ID from FormData
    const partnerId = formData.get('id')?.toString() || ''
    console.log('Updating partner ID:', partnerId);

    // Check if this contains a file
    const hasFile = Array.from(formData.entries()).some(([key, value]) => value instanceof File && value.size > 0)

    if (hasFile) {
      console.log('Edit FormData contains file - using multipart/form-data')

      // Method spoofing for Laravel
      formData.append('_method', 'PUT');

      // FormData with files: Don't manually set Content-Type
      // Let the browser set it with the correct boundary
      const response = await axios.post(
        `${baseUrl}/api/partners/${partnerId}`,
        formData
      )

      console.log('FormData update successful:', response.data)
    } else {
      console.log('Edit FormData has no files - converting to JSON')

      // No file upload: convert to regular JSON and use PUT
      const jsonData = Object.fromEntries(formData.entries())
      delete jsonData.logo // Remove empty logo if it exists

      await axios.put(`${baseUrl}/api/partners/${partnerId}`, jsonData, {
        headers: {
          'Content-Type': 'application/json'
        }
      })
    }

    showPartnerForm.value = false
    editingPartner.value = null
    fetchPartners(page.value)
  } catch (error: any) {
    console.error('Error response:', error.response?.data);
    if (error.response?.status === 422) {
      formError.value = {
        message: error.response.data.message,
        errors: error.response.data.errors
      }
    } else {
      formError.value = { message: 'An unexpected error occurred. Please try again.' }
      console.error('Failed to update partner:', error)
    }
  }
}

const openEditForm = (partner: Partner) => {
  // Transform the partner format to match what PartnerForm expects
  editingPartner.value = {
    id: partner.id,
    attributes: {
      name: partner.attributes.name,
      website: partner.attributes.website || '',
      description: partner.attributes.description || '',
      logo: partner.attributes.logo,
      is_featured: partner.attributes.is_featured || false,
      status: partner.attributes.status || 'active'
    }
  }
  showPartnerForm.value = true
}

onMounted(() => {
  fetchPartners()
})
</script>

<template>
  <div class="space-y-6">
    <div class="sm:flex sm:items-center sm:justify-between">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">Partners</h1>
        <p class="mt-1 text-sm text-gray-500">Manage organization partners and collaborators</p>
      </div>
      <div class="mt-4 sm:mt-0">
        <button
          type="button"
          class="inline-flex items-center rounded-md bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
          @click="showPartnerForm = true; editingPartner = null"
        >
          Add Partner
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
                placeholder="Search partners..."
                @input="fetchPartners(1)"
              />
            </div>
          </div>

          <!-- Status filter -->
          <div class="sm:w-48">
            <select
              v-model="selectedStatus"
              class="block w-full rounded-md border-0 py-2.5 pl-3 pr-10 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-primary-600 sm:text-sm sm:leading-6"
              @change="fetchPartners(1)"
            >
              <option value="">All statuses</option>
              <option v-for="status in statuses" :key="status" :value="status">
                {{ status.charAt(0).toUpperCase() + status.slice(1) }}
              </option>
            </select>
          </div>

          <!-- Clear filters -->
          <button
            v-if="searchQuery || selectedStatus"
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
      <div class="min-h-[536px]">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-300">
            <thead>
              <tr>
                <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Partner</th>
                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Website</th>
                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Status</th>
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
                    <p class="text-sm text-gray-500">Loading partners...</p>
                  </div>
                </td>
              </tr>
              <tr v-else-if="partners.length === 0">
                <td colspan="5" class="px-3 py-4 text-sm text-gray-500 text-center">
                  {{ searchQuery || selectedStatus ? 'No partners match the current filters' : 'No partners found' }}
                </td>
              </tr>
              <tr v-else v-for="partner in partners" :key="partner.id" class="hover:bg-gray-50">
                <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm sm:pl-6">
                  <div class="flex items-center">
                    <div v-if="partner.attributes.logo" class="h-10 w-10 flex-shrink-0 rounded-full overflow-hidden">
                      <img :src="partner.attributes.logo" :alt="partner.attributes.name" class="h-full w-full object-cover" />
                    </div>
                    <div v-else class="h-10 w-10 flex-shrink-0 rounded-full bg-primary-100 flex items-center justify-center">
                      <span class="text-primary-700 font-medium text-sm">
                        {{ partner.attributes?.name?.charAt(0)?.toUpperCase() || '?' }}
                      </span>
                    </div>
                    <div class="ml-4">
                      <div class="font-medium text-gray-900">{{ partner.attributes?.name || 'Unknown Partner' }}</div>
                      <div v-if="partner.attributes.description" class="text-gray-500 line-clamp-1">
                        {{ partner.attributes.description }}
                      </div>
                    </div>
                  </div>
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                  <a 
                    v-if="partner.attributes?.website"
                    :href="partner.attributes.website"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="text-primary-600 hover:text-primary-900"
                  >
                    {{ partner.attributes.website }}
                  </a>
                  <span v-else>No website</span>
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm">
                  <span :class="{
                    'inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset': true,
                    'bg-green-50 text-green-700 ring-green-600/20': partner.attributes?.status === 'active',
                    'bg-yellow-50 text-yellow-700 ring-yellow-600/20': partner.attributes?.status === 'pending',
                    'bg-gray-50 text-gray-700 ring-gray-600/20': partner.attributes?.status === 'inactive' || !partner.attributes?.status
                  }">
                    {{ (partner.attributes?.status?.charAt(0)?.toUpperCase() + partner.attributes?.status?.slice(1)) || 'Inactive' }}
                  </span>
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                  {{ partner.attributes?.created_at ? new Date(partner.attributes.created_at).toLocaleDateString() : 'Unknown date' }}
                </td>
                <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                  <div class="flex items-center justify-end gap-2">
                    <button
                      type="button"
                      class="text-primary-600 hover:text-primary-900"
                      @click="openEditForm(partner)"
                    >
                      <PencilIcon class="h-5 w-5" />
                      <span class="sr-only">Edit</span>
                    </button>
                    <button
                      type="button"
                      class="text-red-600 hover:text-red-900"
                      @click="confirmDelete(partner.id)"
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
      <div class="flex items-center justify-between border-t border-gray-200 px-4 py-3 sm:px-6">
        <div class="flex flex-1 justify-between sm:hidden">
          <button
            :disabled="page === 1"
            @click="fetchPartners(page - 1)"
            class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
            :class="{ 'opacity-50 cursor-not-allowed': page === 1 }"
          >
            Previous
          </button>
          <button
            :disabled="page === meta.pagination.total_pages"
            @click="fetchPartners(page + 1)"
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
                @click="fetchPartners(page - 1)"
                class="relative inline-flex items-center rounded-l-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0"
                :class="{ 'opacity-50 cursor-not-allowed': page === 1 }"
              >
                Previous
              </button>
              <button
                v-for="pageNum in meta.pagination.total_pages"
                :key="pageNum"
                @click="fetchPartners(pageNum)"
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
                @click="fetchPartners(page + 1)"
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

    <!-- Delete Confirmation Dialog -->
    <ConfirmDialog
      :show="showDeleteConfirm"
      title="Delete Partner"
      message="Are you sure you want to delete this partner? This action cannot be undone."
      @close="showDeleteConfirm = false"
      @confirm="handleDeletePartner"
    />

    <!-- Partner Form Modal -->
    <PartnerForm
      v-if="showPartnerForm"
      :edit-mode="!!editingPartner"
      :initial-data="editingPartner || undefined"
      :error="formError"
      @close="showPartnerForm = false; editingPartner = null"
      @submit="editingPartner ? handleEditPartner($event) : handleAddPartner($event)"
    />
  </div>
</template>