<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { XMarkIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  editMode: {
    type: Boolean,
    default: false
  },
  initialData: {
    type: Object,
    default: () => ({})
  },
  error: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['close', 'submit'])

const formData = ref({
  name: '',
  website: '',
  description: '',
  is_featured: false,
  status: 'active'
})

const logo = ref<File | null>(null)
const logoPreview = ref('')

// Populate form data when editing
onMounted(() => {
  if (props.editMode && props.initialData && props.initialData.attributes) {
    formData.value = {
      name: props.initialData.attributes.name || '',
      website: props.initialData.attributes.website || '',
      description: props.initialData.attributes.description || '',
      is_featured: props.initialData.attributes.is_featured || false,
      status: props.initialData.attributes.status || 'active'
    }
    
    if (props.initialData.attributes.logo) {
      logoPreview.value = props.initialData.attributes.logo
    }
  }
})

const handleLogoChange = (event: Event) => {
  const target = event.target as HTMLInputElement
  if (target.files && target.files.length > 0) {
    logo.value = target.files[0]
    logoPreview.value = URL.createObjectURL(target.files[0])
  }
}

const clearLogo = () => {
  logo.value = null
  logoPreview.value = ''
  const fileInput = document.getElementById('logo-upload') as HTMLInputElement
  if (fileInput) {
    fileInput.value = ''
  }
}

const handleSubmit = () => {
  const submitFormData = new FormData()
  
  if (props.editMode && props.initialData?.id) {
    submitFormData.append('id', props.initialData.id)
  }
  
  submitFormData.append('name', formData.value.name)
  submitFormData.append('website', formData.value.website)
  submitFormData.append('description', formData.value.description || '')
  submitFormData.append('is_featured', formData.value.is_featured ? '1' : '0')
  submitFormData.append('status', formData.value.status)
  
  if (logo.value) {
    submitFormData.append('logo', logo.value)
  }
  
  emit('submit', submitFormData)
}

const title = computed(() => props.editMode ? 'Edit Partner' : 'Add Partner')
const submitText = computed(() => props.editMode ? 'Update Partner' : 'Add Partner')

const statuses = [
  { value: 'active', label: 'Active' },
  { value: 'pending', label: 'Pending' },
  { value: 'inactive', label: 'Inactive' }
]
</script>

<template>
  <div class="fixed inset-0 z-10 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex min-h-screen items-center justify-center p-4 text-center sm:p-0">
      <div 
        class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" 
        aria-hidden="true"
        @click="emit('close')"
      ></div>

      <div class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-2xl">
        <form @submit.prevent="handleSubmit">
          <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
            <div class="sm:flex sm:items-start">
              <div class="w-full">
                <div class="flex items-center justify-between">
                  <h3 class="text-xl font-semibold leading-6 text-gray-900">{{ title }}</h3>
                  <button
                    type="button"
                    class="rounded-md bg-white text-gray-400 hover:text-gray-500 focus:outline-none"
                    @click="emit('close')"
                  >
                    <span class="sr-only">Close</span>
                    <XMarkIcon class="h-6 w-6" aria-hidden="true" />
                  </button>
                </div>
                
                <!-- Form Error -->
                <div v-if="error" class="mt-4 rounded-md bg-red-50 p-4">
                  <div class="flex">
                    <div class="text-sm text-red-700">
                      <p>{{ error.message }}</p>
                      <ul v-if="error.errors" class="mt-2 list-disc pl-5 space-y-1">
                        <li v-for="(messages, field) in error.errors" :key="field">
                          {{ messages[0] }}
                        </li>
                      </ul>
                    </div>
                  </div>
                </div>

                <div class="mt-5 space-y-6">
                  <!-- Logo Upload -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Logo</label>
                    <div class="mt-1 flex items-center">
                      <div v-if="logoPreview" class="relative inline-block">
                        <img :src="logoPreview" alt="Logo Preview" class="h-16 w-16 rounded-md object-cover" />
                        <button 
                          type="button"
                          class="absolute -right-2 -top-2 rounded-full bg-red-500 p-1 text-white hover:bg-red-600 focus:outline-none"
                          @click="clearLogo"
                        >
                          <XMarkIcon class="h-4 w-4" />
                        </button>
                      </div>
                      <div v-else class="flex h-16 w-16 items-center justify-center rounded-md border-2 border-dashed border-gray-300 bg-gray-50">
                        <span class="text-xs text-gray-500">No logo</span>
                      </div>
                      <label
                        for="logo-upload"
                        class="ml-4 cursor-pointer rounded-md border border-gray-300 bg-white py-2 px-3 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none"
                      >
                        Upload
                        <input
                          id="logo-upload"
                          name="logo"
                          type="file"
                          class="sr-only"
                          accept="image/*"
                          @change="handleLogoChange"
                        />
                      </label>
                    </div>
                  </div>

                  <!-- Name -->
                  <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                    <div class="mt-1">
                      <input
                        id="name"
                        v-model="formData.name"
                        type="text"
                        class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6"
                        required
                      />
                    </div>
                  </div>

                  <!-- Website -->
                  <div>
                    <label for="website" class="block text-sm font-medium text-gray-700">Website</label>
                    <div class="mt-1">
                      <input
                        id="website"
                        v-model="formData.website"
                        type="url"
                        class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6"
                        placeholder="https://example.com"
                      />
                    </div>
                  </div>

                  <!-- Description -->
                  <div>
                    <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                    <div class="mt-1">
                      <textarea
                        id="description"
                        v-model="formData.description"
                        rows="3"
                        class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6"
                        placeholder="Enter description..."
                      ></textarea>
                    </div>
                  </div>

                  <!-- Status -->
                  <div>
                    <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                    <div class="mt-1">
                      <select
                        id="status"
                        v-model="formData.status"
                        class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6"
                      >
                        <option v-for="status in statuses" :key="status.value" :value="status.value">
                          {{ status.label }}
                        </option>
                      </select>
                    </div>
                  </div>

                  <!-- Featured Toggle -->
                  <div class="relative flex items-start">
                    <div class="flex h-6 items-center">
                      <input
                        id="is_featured"
                        v-model="formData.is_featured"
                        type="checkbox"
                        class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-600"
                      />
                    </div>
                    <div class="ml-3 text-sm leading-6">
                      <label for="is_featured" class="font-medium text-gray-900">Featured Partner</label>
                      <p class="text-gray-500">Featured partners appear prominently on the website.</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
            <button
              type="submit"
              class="inline-flex w-full justify-center rounded-md bg-primary-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-700 focus:outline-none sm:ml-3 sm:w-auto"
            >
              {{ submitText }}
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
</template> 