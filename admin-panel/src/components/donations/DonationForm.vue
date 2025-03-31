<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { XMarkIcon } from '@heroicons/vue/24/outline'
import axios from 'axios'
import Spinner from '../ui/Spinner.vue'

const emit = defineEmits(['close', 'submit'])

const props = defineProps<{
  editMode?: boolean
  initialData?: {
    id?: string
    attributes: {
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
    }
  }
}>()

interface DonationForm {
  user_id: string
  cause_id: string
  amount: number
  is_anonymous: boolean
  cover_fees: boolean
  currency_code: string
  payment_status: string
  payment_method_id: string
  is_gift: boolean
  gift_message: string | null
  recipient_name: string | null
  recipient_email: string | null
}

interface Cause {
  id: string
  attributes: {
    title: string
    slug: string
  }
}

interface User {
  id: string
  attributes: {
    name: string
    email: string
  }
}

const form = ref<DonationForm>({
  user_id: '',
  cause_id: '',
  amount: 0,
  is_anonymous: false,
  cover_fees: false,
  currency_code: 'USD',
  payment_status: 'pending',
  payment_method_id: 'card',
  is_gift: false,
  gift_message: null,
  recipient_name: null,
  recipient_email: null
})

const causes = ref<Cause[]>([])
const users = ref<User[]>([])
const loadingCauses = ref(false)
const loadingUsers = ref(false)
const paymentStatuses = ['pending', 'completed', 'failed', 'refunded']
const errors = ref<Partial<Record<keyof DonationForm, string>>>({})

// Fetch available causes
const fetchCauses = async () => {
  loadingCauses.value = true
  try {
    const baseUrl = import.meta.env.VITE_API_URL
    const response = await axios.get(`${baseUrl}/api/causes`)
    causes.value = response.data.data
  } catch (error) {
    console.error('Failed to fetch causes:', error)
  } finally {
    loadingCauses.value = false
  }
}

// Fetch available users
const fetchUsers = async () => {
  loadingUsers.value = true
  try {
    const baseUrl = import.meta.env.VITE_API_URL
    const response = await axios.get(`${baseUrl}/api/users`)
    users.value = response.data.data
  } catch (error) {
    console.error('Failed to fetch users:', {
      message: error?.message,
      response: error?.response ? {
        status: error.response.status,
        statusText: error.response.statusText,
        data: error.response.data
      } : 'No response'
    })
  } finally {
    loadingUsers.value = false
  }
}

const validateForm = () => {
  errors.value = {}
  
  if (!form.value.user_id) {
    errors.value.user_id = 'User is required'
  }
  
  if (!form.value.amount || form.value.amount <= 0) {
    errors.value.amount = 'Amount must be greater than 0'
  }
  
  if (!form.value.cause_id) {
    errors.value.cause_id = 'Cause is required'
  }
  
  if (form.value.is_gift) {
    if (!form.value.recipient_name?.trim()) {
      errors.value.recipient_name = 'Recipient name is required for gifts'
    }
    if (!form.value.recipient_email?.trim()) {
      errors.value.recipient_email = 'Recipient email is required for gifts'
    }
  }
  
  return Object.keys(errors.value).length === 0
}

const calculateFees = computed(() => {
  const amount = form.value.amount || 0
  const processingFee = amount * 0.029 + 0.30 // Example: 2.9% + $0.30
  return {
    processingFee,
    total: form.value.cover_fees ? amount + processingFee : amount
  }
})

onMounted(() => {
  fetchCauses()
  fetchUsers()
  
  if (props.initialData) {
    form.value = {
      user_id: props.initialData.attributes.user_id,
      cause_id: props.initialData.attributes.cause_id,
      amount: props.initialData.attributes.amount,
      is_anonymous: props.initialData.attributes.is_anonymous,
      cover_fees: props.initialData.attributes.cover_fees,
      currency_code: props.initialData.attributes.currency_code,
      payment_status: props.initialData.attributes.payment_status,
      payment_method_id: props.initialData.attributes.payment_method_id,
      is_gift: props.initialData.attributes.is_gift,
      gift_message: props.initialData.attributes.gift_message,
      recipient_name: props.initialData.attributes.recipient_name,
      recipient_email: props.initialData.attributes.recipient_email
    }
  }
})

const handleSubmit = () => {
  if (validateForm()) {
    emit('submit', { 
      id: props.initialData?.id,
      ...form.value 
    })
  }
}
</script>

<template>
  <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity z-50"></div>
  
  <div class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
      <div class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6">
        <div class="absolute right-0 top-0 pr-4 pt-4">
          <button
            type="button"
            class="rounded-md bg-white text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
            @click="emit('close')"
          >
            <XMarkIcon class="h-6 w-6" />
          </button>
        </div>
        
        <div class="sm:flex sm:items-start">
          <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
            <h3 class="text-lg font-semibold leading-6 text-gray-900">Record New Donation</h3>
            
            <form @submit.prevent="handleSubmit" class="mt-6 space-y-6">
              <div>
                <label for="user" class="block text-sm font-medium leading-6 text-gray-900">Donor</label>
                <div class="mt-2">
                  <select
                    id="user"
                    v-model="form.user_id"
                    :disabled="loadingUsers"
                    :class="[
                      'block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6',
                      { 
                        'opacity-50 cursor-not-allowed': loadingUsers,
                        'ring-red-300 focus:ring-red-500': errors.user_id
                      }
                    ]"
                  >
                    <option value="">{{ loadingUsers ? 'Loading users...' : 'Select a user' }}</option>
                    <option v-for="user in users" :key="user.id" :value="user.id">
                      {{ user.attributes.name }}
                    </option>
                  </select>
                  <p v-if="errors.user_id" class="mt-2 text-sm text-red-600">{{ errors.user_id }}</p>
                </div>
              </div>

              <div>
                <label for="amount" class="block text-sm font-medium leading-6 text-gray-900">Amount ($)</label>
                <div class="mt-2">
                  <input
                    type="number"
                    id="amount"
                    v-model="form.amount"
                    min="0"
                    step="0.01"
                    class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6"
                    :class="{ 'ring-red-300 focus:ring-red-500': errors.amount }"
                  />
                  <p v-if="errors.amount" class="mt-2 text-sm text-red-600">{{ errors.amount }}</p>
                  <div class="mt-2 text-sm text-gray-500">
                    <div>Processing fee: ${{ calculateFees.processingFee.toFixed(2) }}</div>
                    <div>Total amount: ${{ calculateFees.total.toFixed(2) }}</div>
                  </div>
                </div>
              </div>

              <div>
                <label for="cause" class="block text-sm font-medium leading-6 text-gray-900">Cause</label>
                <div class="mt-2">
                  <select
                    id="cause"
                    v-model="form.cause_id"
                    :disabled="loadingCauses"
                    :class="[
                      'block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6',
                      { 
                        'opacity-50 cursor-not-allowed': loadingCauses,
                        'ring-red-300 focus:ring-red-500': errors.cause_id
                      }
                    ]"
                  >
                    <option value="">{{ loadingCauses ? 'Loading causes...' : 'Select a cause' }}</option>
                    <option v-for="cause in causes" :key="cause.id" :value="cause.id">
                      {{ cause.attributes.title }}
                    </option>
                  </select>
                  <p v-if="errors.cause_id" class="mt-2 text-sm text-red-600">{{ errors.cause_id }}</p>
                </div>
              </div>

              <div>
                <div class="flex items-center gap-4">
                  <div class="relative flex items-start">
                    <div class="flex h-6 items-center">
                      <input
                        id="is_anonymous"
                        type="checkbox"
                        v-model="form.is_anonymous"
                        class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-600"
                      />
                    </div>
                    <div class="ml-3 text-sm leading-6">
                      <label for="is_anonymous" class="font-medium text-gray-900">Anonymous donation</label>
                    </div>
                  </div>
                  
                  <div class="relative flex items-start">
                    <div class="flex h-6 items-center">
                      <input
                        id="cover_fees"
                        type="checkbox"
                        v-model="form.cover_fees"
                        class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-600"
                      />
                    </div>
                    <div class="ml-3 text-sm leading-6">
                      <label for="cover_fees" class="font-medium text-gray-900">Cover processing fees</label>
                    </div>
                  </div>
                </div>
              </div>

              <div>
                <div class="relative flex items-start">
                  <div class="flex h-6 items-center">
                    <input
                      id="is_gift"
                      type="checkbox"
                      v-model="form.is_gift"
                      class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-600"
                    />
                  </div>
                  <div class="ml-3 text-sm leading-6">
                    <label for="is_gift" class="font-medium text-gray-900">This is a gift</label>
                  </div>
                </div>
              </div>

              <div v-if="form.is_gift" class="space-y-4">
                <div>
                  <label for="recipient_name" class="block text-sm font-medium leading-6 text-gray-900">Recipient Name</label>
                  <div class="mt-2">
                    <input
                      type="text"
                      id="recipient_name"
                      v-model="form.recipient_name"
                      class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6"
                      :class="{ 'ring-red-300 focus:ring-red-500': errors.recipient_name }"
                    />
                    <p v-if="errors.recipient_name" class="mt-2 text-sm text-red-600">{{ errors.recipient_name }}</p>
                  </div>
                </div>

                <div>
                  <label for="recipient_email" class="block text-sm font-medium leading-6 text-gray-900">Recipient Email</label>
                  <div class="mt-2">
                    <input
                      type="email"
                      id="recipient_email"
                      v-model="form.recipient_email"
                      class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6"
                      :class="{ 'ring-red-300 focus:ring-red-500': errors.recipient_email }"
                    />
                    <p v-if="errors.recipient_email" class="mt-2 text-sm text-red-600">{{ errors.recipient_email }}</p>
                  </div>
                </div>

                <div>
                  <label for="gift_message" class="block text-sm font-medium leading-6 text-gray-900">Gift Message</label>
                  <div class="mt-2">
                    <textarea
                      id="gift_message"
                      v-model="form.gift_message"
                      rows="3"
                      class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6"
                    ></textarea>
                  </div>
                </div>
              </div>

              <div>
                <label for="payment_status" class="block text-sm font-medium leading-6 text-gray-900">Payment Status</label>
                <div class="mt-2">
                  <select
                    id="payment_status"
                    v-model="form.payment_status"
                    class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6"
                  >
                    <option v-for="status in paymentStatuses" :key="status" :value="status">
                      {{ status.charAt(0).toUpperCase() + status.slice(1) }}
                    </option>
                  </select>
                </div>
              </div>

              <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                <button
                  type="submit"
                  class="inline-flex w-full justify-center rounded-md bg-primary-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600 sm:ml-3 sm:w-auto"
                >
                  Record Donation
                </button>
                <button
                  type="button"
                  class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto"
                  @click="emit('close')"
                >
                  Cancel
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>