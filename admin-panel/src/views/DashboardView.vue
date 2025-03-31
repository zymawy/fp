<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { Line } from 'vue-chartjs'
import {
  ChartBarIcon,
  CurrencyDollarIcon,
  UsersIcon,
  HeartIcon,
  UserIcon,
  ChevronRightIcon,
} from '@heroicons/vue/24/outline'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend
} from 'chart.js'
import axios from 'axios'

ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend
)

interface Stats {
  totalDonations: number
  totalUsers: number
  totalCauses: number
  totalPartners: number
  recentDonations: Array<{
    id: number
    amount: number
    donor: string
    cause: string
    date: string
  }>
}

const stats = ref<Stats>({
  totalDonations: 0,
  totalUsers: 0,
  totalCauses: 0,
  totalPartners: 0,
  recentDonations: []
})

const chartData = ref({
  labels: [],
  datasets: [
    {
      label: 'Donations',
      backgroundColor: 'rgba(0, 78, 91, 0.1)',
      borderColor: '#004E5B',
      tension: 0.4,
      fill: true,
      data: []
    }
  ]
})

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    tooltip: {
      backgroundColor: 'rgba(0, 0, 0, 0.8)',
      padding: 12,
      titleFont: {
        size: 14,
        weight: 'bold' as const
      },
      bodyFont: {
        size: 13
      },
      callbacks: {
        label: function(context: any) {
          return `$${context.raw.toLocaleString()}`
        }
      }
    },
    legend: {
      display: false
    }
  },
  scales: {
    y: {
      beginAtZero: true,
      grid: {
        color: 'rgba(0, 78, 91, 0.1)'
      },
      ticks: {
        callback: (value: any) => `$${value.toLocaleString()}`
      }
    },
    x: {
      grid: {
        display: false
      }
    }
  }
}

const loading = ref(true)
const error = ref<string | null>(null)
const chartPeriod = ref('monthly')

const fetchDashboardStats = async () => {
  try {
    loading.value = true
    error.value = null
    const response = await axios.get('/admin/dashboard/stats')
    stats.value = response.data
  } catch (err) {
    console.error('Error fetching dashboard stats:', err)
    error.value = 'Failed to load dashboard statistics'
  } finally {
    loading.value = false
  }
}

const fetchDonationTrends = async () => {
  try {
    const response = await axios.get('/admin/dashboard/trends', {
      params: { period: chartPeriod.value }
    })
    
    chartData.value.labels = response.data.labels
    chartData.value.datasets[0].data = response.data.data
  } catch (err) {
    console.error('Error fetching donation trends:', err)
    error.value = 'Failed to load donation trends'
  }
}

const changeChartPeriod = async (period: string) => {
  chartPeriod.value = period
  await fetchDonationTrends()
}

onMounted(async () => {
  await Promise.all([
    fetchDashboardStats(),
    fetchDonationTrends()
  ])
})
</script>

<template>
  <div class="space-y-8">
    <header class="flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">Dashboard Overview</h1>
        <p class="mt-1 text-sm text-gray-500">Track your platform's performance and recent activities</p>
      </div>
      <div class="flex gap-3">
        <button
          type="button"
          class="inline-flex items-center rounded-md bg-white px-4 py-2.5 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 transition-all focus:outline-none focus:ring-2 focus:ring-primary-500"
        >
          <ChartBarIcon class="h-5 w-5 mr-1.5 text-gray-400" />
          Export Report
        </button>
      </div>
    </header>
    
    <div v-if="error" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
      {{ error }}
    </div>
    
    <!-- Stats -->
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3" role="list" aria-label="Key statistics">
      <div class="bg-white overflow-hidden shadow-lg rounded-xl transition-all hover:shadow-xl hover:scale-[1.02] cursor-pointer" role="listitem">
        <div class="px-4 py-5 sm:p-6">
          <dt class="flex items-center text-sm font-medium text-gray-500">
            <CurrencyDollarIcon class="h-5 w-5 mr-2 text-primary-500" />
            Total Donations
          </dt>
          <dd v-if="loading" class="mt-2 text-3xl font-bold text-primary-600">
            <div class="h-8 w-32 bg-gray-200 rounded animate-pulse"></div>
          </dd>
          <dd v-else class="mt-2 text-3xl font-bold text-primary-600">
            ${{ stats.totalDonations.toLocaleString() }}
            <span class="text-sm font-normal text-gray-500 ml-2">this month</span>
          </dd>
        </div>
      </div>
      <div class="bg-white overflow-hidden shadow-lg rounded-xl transition-all hover:shadow-xl hover:scale-[1.02] cursor-pointer" role="listitem">
        <div class="px-4 py-5 sm:p-6">
          <dt class="flex items-center text-sm font-medium text-gray-500">
            <UsersIcon class="h-5 w-5 mr-2 text-primary-500" />
            Total Users
          </dt>
          <dd v-if="loading" class="mt-2 text-3xl font-bold text-info-600">
            <div class="h-8 w-24 bg-gray-200 rounded animate-pulse"></div>
          </dd>
          <dd v-else class="mt-2 text-3xl font-bold text-info-600">
            {{ stats.totalUsers.toLocaleString() }}
            <span class="text-sm font-normal text-success-500 ml-2">+12%</span>
          </dd>
        </div>
      </div>
      <div class="bg-white overflow-hidden shadow-lg rounded-xl transition-all hover:shadow-xl hover:scale-[1.02] cursor-pointer" role="listitem">
        <div class="px-4 py-5 sm:p-6">
          <dt class="flex items-center text-sm font-medium text-gray-500">
            <HeartIcon class="h-5 w-5 mr-2 text-primary-500" />
            Active Causes
          </dt>
          <dd v-if="loading" class="mt-2 text-3xl font-bold text-warning-600">
            <div class="h-8 w-16 bg-gray-200 rounded animate-pulse"></div>
          </dd>
          <dd v-else class="mt-2 text-3xl font-bold text-warning-600">
            {{ stats.totalCauses }}
            <span class="text-sm font-normal text-gray-500 ml-2">campaigns</span>
          </dd>
        </div>
      </div>
    </div>

    <!-- Chart -->
    <div class="bg-white shadow-lg rounded-xl p-6">
      <div class="flex items-center justify-between mb-6">
        <h2 class="text-lg font-medium text-gray-900">Donation Trends</h2>
        <div class="flex gap-2">
          <button
            type="button"
            :class="[
              'px-4 py-2 text-sm font-medium rounded-md transition-colors focus:outline-none focus:ring-2 focus:ring-info-500',
              chartPeriod === 'monthly' 
                ? 'text-white bg-info-600 hover:bg-info-700' 
                : 'text-gray-700 bg-gray-100 hover:bg-gray-200'
            ]"
            @click="changeChartPeriod('monthly')"
          >
            Monthly
          </button>
          <button
            type="button"
            :class="[
              'px-4 py-2 text-sm font-medium rounded-md transition-colors focus:outline-none focus:ring-2 focus:ring-info-500',
              chartPeriod === 'weekly' 
                ? 'text-white bg-info-600 hover:bg-info-700' 
                : 'text-gray-700 bg-gray-100 hover:bg-gray-200'
            ]"
            @click="changeChartPeriod('weekly')"
          >
            Weekly
          </button>
        </div>
      </div>
      <div v-if="loading" class="h-[400px] flex items-center justify-center">
        <div class="w-full h-64 bg-gray-100 rounded-lg animate-pulse"></div>
      </div>
      <div v-else class="h-[400px]">
        <Line :data="chartData" :options="chartOptions" />
      </div>
    </div>

    <!-- Recent Activity -->
    <div class="bg-white shadow-lg rounded-xl">
      <div class="px-4 py-5 sm:px-6">
        <div class="flex items-center justify-between">
          <h2 class="text-lg font-medium text-gray-900">Recent Donations</h2>
          <button
            type="button"
            class="text-sm font-medium text-primary-600 hover:text-primary-700 transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 rounded-md px-2 py-1"
          >
            View all
          </button>
        </div>
      </div>
      <div class="border-t border-gray-200">
        <div v-if="loading" class="p-4">
          <div v-for="i in 3" :key="i" class="h-16 bg-gray-100 rounded-lg mb-3 animate-pulse"></div>
        </div>
        <ul v-else-if="stats.recentDonations.length > 0" role="list" class="divide-y divide-gray-200">
          <li v-for="donation in stats.recentDonations" :key="donation.id" class="px-4 py-4 sm:px-6">
            <div class="flex items-center justify-between group hover:bg-gray-50 -mx-4 -my-4 px-4 py-4 cursor-pointer transition-colors rounded-lg">
              <div class="flex items-center space-x-3">
                <div class="flex-shrink-0">
                  <div class="h-10 w-10 rounded-full bg-primary-100 flex items-center justify-center">
                    <UserIcon class="h-5 w-5 text-primary-600" />
                  </div>
                </div>
                <div>
                  <p class="text-sm font-medium text-gray-900">{{ donation.donor }}</p>
                  <p class="text-sm text-gray-500">donated to <span class="font-medium text-primary-600">{{ donation.cause }}</span></p>
                </div>
              </div>
              <div class="flex items-center space-x-4">
                <div class="text-right">
                  <p class="text-sm font-medium text-primary-600">${{ donation.amount.toLocaleString() }}</p>
                  <p class="text-xs text-gray-500">{{ donation.date }}</p>
                </div>
                <ChevronRightIcon class="h-5 w-5 text-primary-600 opacity-0 group-hover:opacity-100 transition-opacity" />
              </div>
            </div>
          </li>
        </ul>
        <div v-else class="p-8 text-center">
          <p class="text-gray-500">No recent donations</p>
        </div>
      </div>
    </div>
  </div>
</template>