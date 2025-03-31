<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { Line, Bar, Doughnut } from 'vue-chartjs'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  ArcElement,
  Title,
  Tooltip,
  Legend,
  Filler
} from 'chart.js'
import axios from 'axios'
import dayjs from 'dayjs'
import { ArrowTrendingUpIcon, ArrowTrendingDownIcon } from '@heroicons/vue/24/outline'
import Spinner from '../../components/ui/Spinner.vue'

// Register Chart.js components
ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  ArcElement,
  Title,
  Tooltip,
  Legend,
  Filler
)

interface ReportData {
  donations: {
    total: number
    count: number
    average: number
    percentageChange: number
  }
  causes: {
    active: number
    completed: number
    total: number
  }
  topCauses: Array<{
    name: string
    amount: number
    percentage: number
  }>
  recentDonations: Array<{
    id: string
    amount: number
    donor: string
    cause: string
    date: string
  }>
  donationTrends: Array<{
    date: string
    amount: number
  }>
}

interface ErrorResponse {
  response?: {
    status: number;
    statusText: string;
    data: {
      message?: string;
      error?: string;
    };
  };
  message?: string;
}

const loading = ref(true)
const error = ref<string | null>(null)
const timeRange = ref('week') // week, month, year
const reportData = ref<ReportData>({
  donations: {
    total: 0,
    count: 0,
    average: 0,
    percentageChange: 0
  },
  causes: {
    active: 0,
    completed: 0,
    total: 0
  },
  topCauses: [],
  recentDonations: [],
  donationTrends: []
})

// Chart data
const donationTrendsData = ref({
  labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
  datasets: [{
    label: 'Donations',
    data: [0, 0, 0, 0, 0, 0, 0],
    borderColor: '#004E5B',
    backgroundColor: 'rgba(0, 78, 91, 0.1)',
    tension: 0.4,
    fill: true
  }]
})

const causesDistributionData = ref({
  labels: [],
  datasets: [{
    data: [],
    backgroundColor: [
      'rgba(0, 78, 91, 0.8)',
      'rgba(51, 134, 149, 0.8)',
      'rgba(102, 164, 175, 0.8)',
      'rgba(153, 195, 202, 0.8)',
      'rgba(204, 225, 228, 0.8)'
    ]
  }]
})

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: false
    },
    tooltip: {
      backgroundColor: 'rgba(0, 0, 0, 0.8)',
      padding: 12,
      titleFont: {
        size: 14,
        weight: 'bold'
      },
      bodyFont: {
        size: 13
      },
      callbacks: {
        label: function(context: any) {
          return `$${context.raw.toLocaleString()}`
        }
      }
    }
  },
  scales: {
    y: {
      beginAtZero: true,
      grid: {
        color: 'rgba(0, 0, 0, 0.1)'
      },
      ticks: {
        callback: (value: number) => `$${value.toLocaleString()}`
      }
    }
  }
}

const doughnutOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'right' as const,
      labels: {
        font: {
          size: 12
        }
      }
    }
  }
}

const fetchReportData = async () => {
  loading.value = true
  error.value = null
  
  try {
    const baseUrl = import.meta.env.VITE_API_URL
    const response = await axios.get(`${baseUrl}/api/reports?timeRange=${timeRange.value}`)
    reportData.value = response.data.data

    // Update chart data
    updateChartData(response.data.data)
  } catch (err: any) {
    console.error('Failed to fetch report data:', {
      message: err?.message,
      response: err?.response ? {
        status: err.response.status,
        statusText: err.response.statusText,
        data: err.response.data
      } : 'No response'
    })
    
    // Extract specific error message from response if available
    if (err.response?.data?.message) {
      error.value = err.response.data.message
    } else if (err.response?.data?.error) {
      error.value = err.response.data.error
    } else if (err.message) {
      error.value = err.message
    } else {
      error.value = 'Failed to load report data. Please try again later.'
    }
  } finally {
    loading.value = false
  }
}

const updateChartData = (data: ReportData) => {
  // Update donation trends chart
  if (data.donationTrends && data.donationTrends.length > 0) {
    donationTrendsData.value.labels = data.donationTrends.map(item => item.date)
    donationTrendsData.value.datasets[0].data = data.donationTrends.map(item => item.amount)
  }

  // Update causes distribution chart
  if (data.topCauses && data.topCauses.length > 0) {
    causesDistributionData.value.labels = data.topCauses.map(cause => cause.name)
    causesDistributionData.value.datasets[0].data = data.topCauses.map(cause => cause.amount)
  }
}

const formatAmount = (amount: number) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(amount)
}

const formatDate = (date: string) => {
  return dayjs(date).format('MMM D, YYYY')
}

onMounted(() => {
  fetchReportData()
})
</script>

<template>
  <div class="space-y-6">
    <div class="sm:flex sm:items-center sm:justify-between">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">Reports & Analytics</h1>
        <p class="mt-1 text-sm text-gray-500">Monitor donation trends and campaign performance</p>
      </div>
      <div class="mt-4 sm:mt-0">
        <select
          v-model="timeRange"
          @change="fetchReportData"
          class="block w-full rounded-md border-0 py-1.5 pl-3 pr-10 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-primary-600 sm:text-sm sm:leading-6"
        >
          <option value="week">Last 7 Days</option>
          <option value="month">Last 30 Days</option>
          <option value="year">Last 12 Months</option>
        </select>
      </div>
    </div>

    <div v-if="loading" class="flex items-center justify-center h-96">
      <Spinner size="lg" color="primary" />
    </div>
    
    <div v-else-if="error" class="flex items-center justify-center h-96">
      <div class="text-center">
        <div class="text-red-500 text-xl mb-4">{{ error }}</div>
        <button 
          @click="fetchReportData" 
          class="px-4 py-2 bg-primary-600 text-white rounded-md hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
        >
          Try Again
        </button>
      </div>
    </div>

    <div v-else class="space-y-6">
      <!-- Stats Grid -->
      <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Total Donations -->
        <div class="bg-white overflow-hidden shadow-lg rounded-xl px-4 py-5 sm:p-6">
          <dt class="text-sm font-medium text-gray-500">Total Donations</dt>
          <dd class="mt-1 flex items-baseline justify-between md:block lg:flex">
            <div class="flex items-baseline text-2xl font-semibold text-primary-600">
              {{ formatAmount(reportData.donations.total) }}
            </div>
            <div class="flex items-baseline text-sm font-semibold" :class="[
              reportData.donations.percentageChange >= 0 ? 'text-green-600' : 'text-red-600'
            ]">
              <component :is="reportData.donations.percentageChange >= 0 ? ArrowTrendingUpIcon : ArrowTrendingDownIcon" 
                class="h-4 w-4 mr-1" 
              />
              {{ Math.abs(reportData.donations.percentageChange) }}%
            </div>
          </dd>
        </div>

        <!-- Number of Donations -->
        <div class="bg-white overflow-hidden shadow-lg rounded-xl px-4 py-5 sm:p-6">
          <dt class="text-sm font-medium text-gray-500">Number of Donations</dt>
          <dd class="mt-1 text-2xl font-semibold text-primary-600">
            {{ reportData.donations.count.toLocaleString() }}
          </dd>
        </div>

        <!-- Average Donation -->
        <div class="bg-white overflow-hidden shadow-lg rounded-xl px-4 py-5 sm:p-6">
          <dt class="text-sm font-medium text-gray-500">Average Donation</dt>
          <dd class="mt-1 text-2xl font-semibold text-primary-600">
            {{ formatAmount(reportData.donations.average) }}
          </dd>
        </div>

        <!-- Active Causes -->
        <div class="bg-white overflow-hidden shadow-lg rounded-xl px-4 py-5 sm:p-6">
          <dt class="text-sm font-medium text-gray-500">Active Causes</dt>
          <dd class="mt-1 text-2xl font-semibold text-primary-600">
            {{ reportData.causes.active }} / {{ reportData.causes.total }}
          </dd>
        </div>
      </div>

      <!-- Charts -->
      <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <!-- Donation Trends -->
        <div class="bg-white shadow-lg rounded-xl p-6">
          <h3 class="text-lg font-medium text-gray-900 mb-6">Donation Trends</h3>
          <div class="h-80">
            <Line :data="donationTrendsData" :options="chartOptions" />
          </div>
        </div>

        <!-- Causes Distribution -->
        <div class="bg-white shadow-lg rounded-xl p-6">
          <h3 class="text-lg font-medium text-gray-900 mb-6">Causes Distribution</h3>
          <div class="h-80">
            <Doughnut :data="causesDistributionData" :options="doughnutOptions" />
          </div>
        </div>
      </div>

      <!-- Recent Donations -->
      <div class="bg-white shadow-lg rounded-xl overflow-hidden">
        <div class="px-4 py-5 sm:px-6">
          <h3 class="text-lg font-medium text-gray-900">Recent Donations</h3>
        </div>
        <div class="border-t border-gray-200">
          <ul role="list" class="divide-y divide-gray-200">
            <li v-for="donation in reportData.recentDonations" :key="donation.id" class="px-4 py-4 sm:px-6">
              <div class="flex items-center justify-between">
                <div class="flex items-center">
                  <div class="flex-shrink-0">
                    <div class="h-10 w-10 rounded-full bg-primary-100 flex items-center justify-center">
                      <span class="text-primary-700 font-medium">
                        {{ donation.donor.charAt(0) }}
                      </span>
                    </div>
                  </div>
                  <div class="ml-4">
                    <div class="font-medium text-gray-900">{{ donation.donor }}</div>
                    <div class="text-sm text-gray-500">{{ donation.cause }}</div>
                  </div>
                </div>
                <div class="text-right">
                  <div class="text-sm font-medium text-primary-600">
                    {{ formatAmount(donation.amount) }}
                  </div>
                  <div class="text-xs text-gray-500">
                    {{ formatDate(donation.date) }}
                  </div>
                </div>
              </div>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</template>