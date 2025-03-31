<script setup lang="ts">
import { ref, watch } from 'vue'
import { useRouter } from 'vue-router'

const isNavigating = ref(false)
const router = useRouter()

router.beforeEach(() => {
  isNavigating.value = true
})

router.afterEach(() => {
  // Small delay to make the transition visible
  setTimeout(() => {
    isNavigating.value = false
  }, 200)
})
</script>

<template>
  <div
    class="fixed top-0 left-0 right-0 h-0.5 bg-gray-100 z-50"
    :class="{ 'transition-all duration-300': !isNavigating }"
  >
    <div
      class="h-full bg-primary-600 transition-all duration-300 ease-in-out"
      :class="isNavigating ? 'w-full' : 'w-0'"
    ></div>
  </div>
</template>