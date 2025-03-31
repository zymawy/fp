<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { MagnifyingGlassIcon, FunnelIcon, PencilIcon, TrashIcon, ExclamationTriangleIcon, CheckCircleIcon } from '@heroicons/vue/24/outline'
import DonationForm from '../../components/donations/DonationForm.vue'
import ConfirmDialog from '../../components/ui/ConfirmDialog.vue'
import dayjs from 'dayjs'
import Spinner from '../../components/ui/Spinner.vue'
import axios from 'axios'

interface DonationAttributes {
  user_id: string
  cause_id: string
  amount: number
  total_amount: number
  processing_fee: number
  is_anonymous: boolean
  cover_fees: boolean
  currency_code: string
  payment_status: string
  payment_method_id: string
  payment_id: string
  is_gift: boolean
  gift_message: string | null
  recipient_name: string | null
  recipient_email: string | null
  created_at: string
  updated_at: string
}

interface Donation {
  id: string
  type: string | null
  attributes: DonationAttributes
  relationships: {
    user: {
      data: {
        type: null
        id: string
      }
    }
    cause: {
      data: {
        type: null
        id: string
      }
    }
  }
}

interface IncludedItem {
  type: null
  id: string
  attributes: {
    name?: string
    email?: string
    title?: string
    slug?: string
  }
}

const donations = ref<Donation[]>([])
const included = ref<IncludedItem[]>([])
const meta = ref({
  pagination: {
    total: 0,
    count: 0,
    per_page: 10,
    current_page: 1,
    total_pages: 1
  }
})

const getDonorName = (donation: Donation) => {
  if (donation.attributes.is_anonymous) return 'Anonymous'
  const user = included.value.find(item => item.id === donation.relationships.user.data.id)
  return user?.attributes.name || 'Unknown'
}

const getCauseTitle = (donation: Donation) => {
  const cause = included.value.find(item => item.id === donation.relationships.cause.data.id)
  return cause?.attributes.title || 'Unknown Cause'
}

const formatAmount = (amount: number, currencyCode: string) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: currencyCode || 'USD'
  }).format(amount)
}

const loading = ref(true)
const searchQuery = ref('')
const selectedStatus = ref('')
const page = ref(1)
const perPage = ref(10)
const showDonationForm = ref(false)
const editingDonation = ref<Donation | null>(null)
const deleteError = ref('')
const deleteSuccess = ref(false)
const showDeleteConfirm = ref(false)
const donationToDelete = ref<string | null>(null)

const statuses = ['pending', 'completed', 'failed', 'refunded']

const buildQueryParams = (page: number) => {
  const params = new URLSearchParams()
  params.append('page', page.toString())
  params.append('per_page', perPage.value.toString())

  if (searchQuery.value) params.append('search', searchQuery.value)
  if (selectedStatus.value) params.append('status', selectedStatus.value)

  return params.toString()
}

const fetchDonations = async (pageNum: number = 1) => {
  loading.value = true
  try {
    const baseUrl = import.meta.env.VITE_API_URL
    const params = buildQueryParams(pageNum)
    const response = await axios.get(`${baseUrl}/api/donations${params ? `?${params}` : ''}`)
    donations.value = response.data.data
    included.value = response.data.included || []
    meta.value = response.data.meta
  } catch (error) {
    console.error('Failed to fetch donations:', error)
    donations.value = []
  } finally {
    loading.value = false
  }
}

const clearFilters = () => {
  searchQuery.value = ''
  selectedStatus.value = ''
  page.value = 1
  fetchDonations(1)
}

const handleAddDonation = async (donationData: any) => {
  try {
    const baseUrl = import.meta.env.VITE_API_URL
    await axios.post(`${baseUrl}/api/donations`, donationData)
    showDonationForm.value = false
    fetchDonations(page.value)
  } catch (error) {
    console.error('Failed to add donation:', error)
  }
}

const handleEditDonation = async (donationData: any) => {
  try {
    const baseUrl = import.meta.env.VITE_API_URL
    await axios.put(`${baseUrl}/api/donations/${donationData.id}`, donationData)
    showDonationForm.value = false
    editingDonation.value = null
    fetchDonations(page.value)
  } catch (error) {
    console.error('Failed to update donation:', error)
  }
}

const confirmDelete = (donationId: string) => {
  donationToDelete.value = donationId
  showDeleteConfirm.value = true
}

const handleDeleteDonation = async () => {
  deleteError.value = ''
  deleteSuccess.value = false
  
  try {
    const baseUrl = import.meta.env.VITE_API_URL
    const response = await axios.delete(`${baseUrl}/api/donations/${donationToDelete.value}`)
    
    if (response.status === 204 || response.status === 200) {
      deleteSuccess.value = true
      fetchDonations(page.value)
      showDeleteConfirm.value = false
      donationToDelete.value = null
    } else {
      throw new Error('Unexpected response status')
    }
  } catch (error: any) {
    console.error('Failed to delete donation:', {
      message: error?.message,
      response: error?.response ? {
        status: error.response.status,
        statusText: error.response.statusText,
        data: error.response.data
      } : 'No response'
    })
    
    deleteError.value = error?.response?.data?.message || 
      error?.response?.data?.error || 
      'Failed to delete donation. Please try again.'
  }
}

const openEditForm = (donation: Donation) => {
  editingDonation.value = donation
  showDonationForm.value = true
}

onMounted(async () => {
  fetchDonations()
})
</script>

<template>
  <div class="space-y-6">
    <div class="sm:flex sm:items-center sm:justify-between">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">Donations</h1>
        <p class="mt-1 text-sm text-gray-500">Manage and monitor donations</p>
      </div>
      <div class="mt-4 sm:mt-0">
        <button
          type="button"
          class="inline-flex items-center rounded-md bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
          @click="showDonationForm = true"
        >
          Add Donation
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
                placeholder="Search donations..."
                @input="fetchDonations(1)"
              />
            </div>
          </div>

          <!-- Status filter -->
          <div class="sm:w-48">
            <select
              v-model="selectedStatus"
              class="block w-full rounded-md border-0 py-2.5 pl-3 pr-10 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-primary-600 sm:text-sm sm:leading-6"
              @change="fetchDonations(1)"
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
    <div v-if="deleteSuccess" class="rounded-md bg-green-50 p-4 mb-4">
      <div class="flex">
        <div class="flex-shrink-0">
          <CheckCircleIcon class="h-5 w-5 text-green-400" aria-hidden="true" />
        </div>
        <div class="ml-3">
          <p class="text-sm font-medium text-green-800">
            Donation was successfully deleted
          </p>
        </div>
      </div>
    </div>

    <div v-if="deleteError" class="rounded-md bg-red-50 p-4 mb-4">
      <div class="flex">
        <div class="flex-shrink-0">
          <ExclamationTriangleIcon class="h-5 w-5 text-red-400" aria-hidden="true" />
        </div>
        <div class="ml-3">
          <h3 class="text-sm font-medium text-red-800">Error</h3>
          <div class="mt-2 text-sm text-red-700">
            {{ deleteError }}
          </div>
        </div>
      </div>
    </div>

    <div class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl">
      <div class="min-h-[536px]">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-300">
            <thead>
              <tr>
                <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Donor</th>
                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Amount</th>
                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Status</th>
                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Date</th>
                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Notes</th>
                <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                  <span class="sr-only">Actions</span>
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-if="loading">
                <td colspan="6" class="px-3 py-12 text-sm text-gray-500">
                  <div class="flex flex-col items-center justify-center gap-3">
                    <Spinner size="md" color="primary" />
                    <p class="text-sm text-gray-500">Loading donations...</p>
                  </div>
                </td>
              </tr>
              <tr v-else-if="donations.length === 0">
                <td colspan="6" class="px-3 py-4 text-sm text-gray-500 text-center">
                  {{ searchQuery || selectedStatus ? 'No donations match the current filters' : 'No donations found' }}
                </td>
              </tr>
              <tr v-else v-for="donation in donations" :key="donation.id" class="hover:bg-gray-50">
                <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">
                  <div>
                    <div>{{ getDonorName(donation) }}</div>
                    <div class="text-xs text-gray-500">{{ getCauseTitle(donation) }}</div>
                  </div>
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900">
                  <div>
                    <div>{{ formatAmount(donation.attributes.total_amount, donation.attributes.currency_code) }}</div>
                    <div class="text-xs text-gray-500">
                      Fee: {{ formatAmount(donation.attributes.processing_fee, donation.attributes.currency_code) }}
                    </div>
                  </div>
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm">
                  <span :class="{
                    'inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset': true,
                    'bg-yellow-50 text-yellow-700 ring-yellow-600/20': donation.attributes.payment_status === 'pending',
                    'bg-green-50 text-green-700 ring-green-600/20': donation.attributes.payment_status === 'completed',
                    'bg-red-50 text-red-700 ring-red-600/20': donation.attributes.payment_status === 'failed',
                    'bg-gray-50 text-gray-700 ring-gray-600/20': donation.attributes.payment_status === 'refunded'
                  }">
                    {{ donation.attributes.payment_status ? (donation.attributes.payment_status.charAt(0).toUpperCase() + donation.attributes.payment_status.slice(1)) : 'Unknown' }}
                  </span>
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                  {{ dayjs(donation.attributes.created_at).format('MMM D, YYYY') }}
                </td>
                <td class="px-3 py-4 text-sm text-gray-500">
                  <div v-if="donation.attributes.is_gift" class="space-y-1">
                    <div class="font-medium">Gift to: {{ donation.attributes.recipient_name }}</div>
                    <div class="text-xs">{{ donation.attributes.gift_message || 'No message' }}</div>
                  </div>
                  <div v-else>-</div>
                </td>
                <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                  <div class="flex items-center justify-end gap-2">
                    <button
                      type="button"
                      class="text-primary-600 hover:text-primary-900"
                      @click="openEditForm(donation)"
                    >
                      <PencilIcon class="h-5 w-5" />
                      <span class="sr-only">Edit</span>
                    </button>
                    <button
                      type="button"
                      class="text-red-600 hover:text-red-900"
                      @click="confirmDelete(donation.id)"
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
            @click="fetchDonations(page - 1)"
            class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
            :class="{ 'opacity-50 cursor-not-allowed': page === 1 }"
          >
            Previous
          </button>
          <button
            :disabled="page === meta.pagination.total_pages"
            @click="fetchDonations(page + 1)"
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
                @click="fetchDonations(page - 1)"
                class="relative inline-flex items-center rounded-l-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0"
                :class="{ 'opacity-50 cursor-not-allowed': page === 1 }"
              >
                Previous
              </button>
              <button
                v-for="pageNum in meta.pagination.total_pages"
                :key="pageNum"
                @click="fetchDonations(pageNum)"
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
                @click="fetchDonations(page + 1)"
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
    
    <!-- Donation Form Modal -->
    <DonationForm
      v-if="showDonationForm"
      :edit-mode="!!editingDonation"
      :initial-data="editingDonation"
      @close="showDonationForm = false; editingDonation = null"
      @submit="editingDonation ? handleEditDonation($event) : handleAddDonation($event)"
    />
    
    <!-- Delete Confirmation Dialog -->
    <ConfirmDialog
      :show="showDeleteConfirm"
      title="Delete Donation"
      message="Are you sure you want to delete this donation? This action cannot be undone."
      @close="showDeleteConfirm = false"
      @confirm="handleDeleteDonation"
    />
  </div>
</template>