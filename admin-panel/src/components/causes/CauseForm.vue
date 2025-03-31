<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { XMarkIcon, PhotoIcon } from '@heroicons/vue/24/outline'
import axios from 'axios'

const emit = defineEmits(['close', 'submit'])

const props = defineProps<{
  error?: { message: string; errors?: Record<string, string[]> } | null
  editMode?: boolean
  initialData?: {
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
}>()

interface CauseForm {
  title: string
  description: string
  goal_amount: number
  category_id: string
  status: string
  image?: File | null
}

interface Category {
  id: string
  attributes: {
    name: string
    slug: string
  }
}

const form = ref<CauseForm>({
  title: '',
  description: '',
  goal_amount: 0,
  category_id: '',
  status: 'active',
  image: null
})

const categories = ref<Category[]>([])
const loadingCategories = ref(false)
const imagePreview = ref<string | null>(null)
const hasExistingImage = ref(false)
const existingImageUrl = ref('')

const statuses = ['active', 'inactive', 'completed']
const errors = ref<Partial<Record<keyof CauseForm, string>>>({})

const fetchCategories = async () => {
  loadingCategories.value = true
  try {
    const baseUrl = import.meta.env.VITE_API_URL
    const response = await axios.get(`${baseUrl}/api/categories`)
    categories.value = response.data.data
  } catch (error) {
    console.error('Failed to fetch categories:', {
      message: error?.message,
      response: error?.response ? {
        status: error.response.status,
        statusText: error.response.statusText,
        data: error.response.data
      } : 'No response'
    })
  } finally {
    loadingCategories.value = false
  }
}

onMounted(() => {
  fetchCategories()
  if (props.initialData) {
    form.value = {
      title: props.initialData.attributes.title,
      description: props.initialData.attributes.description,
      goal_amount: props.initialData.attributes.goal_amount,
      category_id: props.initialData.attributes.category_id,
      status: props.initialData.attributes.status,
      image: null
    }

    if (props.initialData.attributes.image) {
      hasExistingImage.value = true
      existingImageUrl.value = props.initialData.attributes.image
    }
  }
})

const validateForm = () => {
  errors.value = {}

  if (!form.value.title.trim()) {
    errors.value.title = 'Title is required'
  }

  if (!form.value.description.trim()) {
    errors.value.description = 'Description is required'
  }

  if (!form.value.goal_amount || form.value.goal_amount <= 0) {
    errors.value.goal_amount = 'Goal amount must be greater than 0'
  }

  if (!form.value.category_id) {
    errors.value.category_id = 'Category is required'
  }

  // Validate image file if one is selected
  if (form.value.image) {
    if (!(form.value.image instanceof File)) {
      errors.value.image = 'Invalid image file format'
      console.error('Image is not a File object:', form.value.image);
    } else if (!form.value.image.type.startsWith('image/')) {
      errors.value.image = 'File must be an image'
      console.error('File is not an image:', form.value.image.type);
    } else if (form.value.image.size > 2 * 1024 * 1024) { // 2MB limit
      errors.value.image = 'Image size must be less than 2MB'
      console.error('Image too large:', form.value.image.size);
    }
  }

  return Object.keys(errors.value).length === 0
}

const handleFileChange = (event: Event) => {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]

  // Clear any previous errors
  errors.value.image = undefined

  if (!file) {
    form.value.image = null
    imagePreview.value = null
    return
  }

  // Validate file is an image
  if (!file.type.startsWith('image/')) {
    errors.value.image = 'The selected file must be an image'
    form.value.image = null
    imagePreview.value = null
    return
  }

  // Check file size (2MB max)
  if (file.size > 2 * 1024 * 1024) {
    errors.value.image = 'Image size must be less than 2MB'
    form.value.image = null
    imagePreview.value = null
    return
  }

  // Valid image file
  form.value.image = file
  imagePreview.value = URL.createObjectURL(file)
  hasExistingImage.value = false

  console.log('Image selected:', file.name, file.type, file.size);
}

const handleSubmit = () => {
  if (validateForm()) {
    console.log('Form is valid, preparing FormData');

    // Create a FormData object to handle file uploads
    const formData = new FormData();

    // Add the required fields
    formData.append('title', form.value.title);
    formData.append('description', form.value.description);
    formData.append('goal_amount', form.value.goal_amount.toString());
    formData.append('category_id', form.value.category_id);
    formData.append('status', form.value.status);

    // Add the ID if it's an edit operation
    if (props.initialData?.id) {
      formData.append('id', props.initialData.id);
    }

    // Add the image file if it exists and is a valid File object
    if (form.value.image instanceof File) {
      console.log('Adding valid image file to FormData', {
        name: form.value.image.name,
        type: form.value.image.type,
        size: form.value.image.size
      });
      formData.append('featured_image', form.value.image);
    }

    // Debug output for FormData contents
    console.log('FormData entries:');
    formData.forEach((value, key) => {
      console.log(`${key}: ${value instanceof File ? `File: ${value.name}` : value}`);
    });

    // Pass the form data to the parent component
    emit('submit', formData);
  }
}

const clearImage = () => {
  form.value.image = null
  imagePreview.value = null
  hasExistingImage.value = false

  // Reset the file input
  const fileInput = document.getElementById('image-upload') as HTMLInputElement
  if (fileInput) {
    fileInput.value = ''
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
            <h3 class="text-lg font-semibold leading-6 text-gray-900">
              {{ editMode ? 'Edit Cause' : 'Create New Cause' }}
            </h3>

            <!-- Error Alert -->
            <div v-if="error" class="mt-4 rounded-md bg-red-50 p-4">
              <div class="flex">
                <div class="flex-shrink-0">
                  <XMarkIcon class="h-5 w-5 text-red-400" aria-hidden="true" />
                </div>
                <div class="ml-3">
                  <h3 class="text-sm font-medium text-red-800">
                    {{ error.message }}
                  </h3>
                  <div v-if="error.errors" class="mt-2 text-sm text-red-700">
                    <ul role="list" class="list-disc space-y-1 pl-5">
                      <li v-for="(messages, field) in error.errors" :key="field">
                        {{ messages[0] }}
                      </li>
                    </ul>
                  </div>
                </div>
              </div>
            </div>

            <form @submit.prevent="handleSubmit" class="mt-6 space-y-6">
              <!-- Image Upload Field -->
              <div>
                <label for="image-upload" class="block text-sm font-medium leading-6 text-gray-900">
                  Image
                </label>
                <div class="mt-2 flex items-center gap-x-3">
                  <div v-if="imagePreview || hasExistingImage" class="relative">
                    <img
                      :src="imagePreview || existingImageUrl"
                      alt="Preview"
                      class="h-24 w-24 rounded-md object-cover"
                    />
                    <button
                      type="button"
                      @click="clearImage"
                      class="absolute -top-2 -right-2 inline-flex h-6 w-6 items-center justify-center rounded-full bg-red-100 text-red-600 hover:bg-red-200 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
                    >
                      <span class="sr-only">Remove</span>
                      <XMarkIcon class="h-4 w-4" aria-hidden="true" />
                    </button>
                  </div>
                  <div v-else class="flex h-24 w-24 items-center justify-center rounded-md border border-dashed border-gray-300 bg-gray-50">
                    <PhotoIcon class="h-8 w-8 text-gray-300" aria-hidden="true" />
                  </div>
                  <div>
                    <label
                      for="image-upload"
                      class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 cursor-pointer"
                    >
                      {{ imagePreview || existingImageUrl ? 'Change Image' : 'Upload Image' }}
                    </label>
                    <input
                      id="image-upload"
                      name="image-upload"
                      type="file"
                      accept="image/*"
                      class="sr-only"
                      @change="handleFileChange"
                    />
                    <p class="mt-2 text-xs text-gray-500">PNG, JPG, GIF up to 2MB</p>
                  </div>
                </div>
                <p v-if="errors.image" class="mt-2 text-sm text-red-600">{{ errors.image }}</p>
              </div>

              <div>
                <label for="title" class="block text-sm font-medium leading-6 text-gray-900">Title</label>
                <div class="mt-2">
                  <input
                    type="text"
                    id="title"
                    v-model="form.title"
                    class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6"
                    :class="{ 'ring-red-300 focus:ring-red-500': errors.title || error?.errors?.title }"
                  />
                  <p v-if="errors.title || error?.errors?.title" class="mt-2 text-sm text-red-600">
                    {{ errors.title || error?.errors?.title?.[0] }}
                  </p>
                </div>
              </div>

              <div>
                <label for="description" class="block text-sm font-medium leading-6 text-gray-900">Description</label>
                <div class="mt-2">
                  <textarea
                    id="description"
                    v-model="form.description"
                    rows="3"
                    class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6"
                    :class="{ 'ring-red-300 focus:ring-red-500': errors.description }"
                  ></textarea>
                  <p v-if="errors.description" class="mt-2 text-sm text-red-600">{{ errors.description }}</p>
                </div>
              </div>

              <div>
                <label for="goal_amount" class="block text-sm font-medium leading-6 text-gray-900">Goal Amount ($)</label>
                <div class="mt-2">
                  <input
                    type="number"
                    id="goal_amount"
                    v-model="form.goal_amount"
                    min="0"
                    step="0.01"
                    class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6"
                    :class="{ 'ring-red-300 focus:ring-red-500': errors.goal_amount }"
                  />
                  <p v-if="errors.goal_amount" class="mt-2 text-sm text-red-600">{{ errors.goal_amount }}</p>
                </div>
              </div>

              <div>
                <label for="category" class="block text-sm font-medium leading-6 text-gray-900">Category</label>
                <div class="mt-2">
                  <select
                    id="category"
                    v-model="form.category_id"
                    :disabled="loadingCategories"
                    :class="[
                      'block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6',
                      {
                        'opacity-50 cursor-not-allowed': loadingCategories,
                        'ring-red-300 focus:ring-red-500': errors.category_id
                      }
                    ]"
                  >
                    <option value="">{{ loadingCategories ? 'Loading categories...' : 'Select a category' }}</option>
                    <option v-for="category in categories" :key="category.id" :value="category.id" class="py-2">
                      {{ category.attributes.name }}
                    </option>
                  </select>
                  <p v-if="errors.category_id" class="mt-2 text-sm text-red-600">{{ errors.category_id }}</p>
                </div>
              </div>

              <div>
                <label for="status" class="block text-sm font-medium leading-6 text-gray-900">Status</label>
                <div class="mt-2">
                  <select
                    id="status"
                    v-model="form.status"
                    class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6"
                  >
                    <option v-for="status in statuses" :key="status" :value="status">
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
                  {{ editMode ? 'Update' : 'Create' }}
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
