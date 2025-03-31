// Types for API responses
interface User {
  id: string;
  name: string;
  email: string;
  first_name?: string;
  last_name?: string;
  avatar_url?: string;
  phone_number?: string;
  created_at: string;
  updated_at: string;
}

interface Cause {
  id: string;
  title: string;
  description: string;
  image_url?: string;
  imageUrl?: string;
  featured_image?: string;
  goal_amount?: number;
  target_amount?: number;
  goalAmount?: number;
  raised_amount?: number;
  current_amount?: number;
  raisedAmount?: number;
  donor_count?: number;
  donors_count?: number;
  donorCount?: number;
  category_id?: string;
  categoryId?: string;
  category?: {
    id: string;
    name: string;
    slug?: string;
  };
  status?: string;
  urgency_level?: string;
  urgencyLevel?: string;
  location?: string;
  start_date?: string | Date;
  startDate?: string | Date;
  end_date?: string | Date;
  endDate?: string | Date;
  is_featured?: boolean;
  featured?: boolean;
  slider_button_text?: string;
  sliderButtonText?: string;
  slider_subtitle?: string;
  sliderSubtitle?: string;
  progress_percentage?: number;
  progressPercentage?: number;
  created_at?: string;
  updated_at?: string;
}

// Define the API base URL - this will be proxied by Vite during development
// Since we have proxies set up in vite.config.ts, we can use empty prefix
// const API_BASE_URL = '/api';  // Use the proxy set up in Vite config
// Add /api prefix to ensure all requests go to the correct API endpoints
// const API_BASE_URL = 'http://127.0.0.1:8001/api';  // Direct call to the external API
const API_BASE_URL = 'https://0046-64-137-246-210.ngrok-free.app/api';  // ngrok URL

// Mock database for storing donations when API server is unavailable
const LOCAL_STORAGE_DB_KEY = 'local_database';

// Initialize or get the local database
function getLocalDatabase() {
  const stored = localStorage.getItem(LOCAL_STORAGE_DB_KEY);
  if (stored) {
    try {
      return JSON.parse(stored);
    } catch (e) {
      console.error('Error parsing local database:', e);
    }
  }
  return { donations: [], users: [] };
}

// Save to local database
function saveToLocalDatabase(collection: string, data: any) {
  const db = getLocalDatabase();
  if (!db[collection]) db[collection] = [];
  
  // Add ID if not present
  if (!data.id) data.id = `local_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
  
  // Add timestamps
  data.createdAt = data.createdAt || new Date().toISOString();
  data.updatedAt = new Date().toISOString();
  
  // Add to collection
  db[collection].push(data);
  
  // Save back to localStorage
  localStorage.setItem(LOCAL_STORAGE_DB_KEY, JSON.stringify(db));
  
  console.log(`Saved to local database (${collection}):`, data);
  return data;
}

// Default timeout for API requests (10 seconds)
const API_TIMEOUT = 10000;

/**
 * Helper function to fetch from the API with proper error handling and resilience
 * @param endpoint The API endpoint to call
 * @param options Request options
 * @param mockResponse Optional mock response to use when API is unavailable
 * @returns Promise resolving to API response
 */
export async function fetchApi<T>(
  endpoint: string, 
  options: RequestInit = {}, 
  mockResponse?: T
): Promise<T | any> {
  // Ensure endpoint starts with a slash if not already
  const path = endpoint.startsWith('/') ? endpoint : `/${endpoint}`;
  // Construct full URL with API_BASE_URL
  const url = `${API_BASE_URL}${path}`;
  
  console.log(`Making API request to: ${url}`);
  
  // Initialize headers if not present
  if (!options.headers) {
    options.headers = {};
  }
  
  // We need to cast headers to Record<string, string> to avoid TypeScript errors
  const headers = options.headers as Record<string, string>;
  
  // If there's a body, handle it appropriately
  if (options.body) {
    // Don't process FormData - browser will handle content-type with boundary
    if (!(options.body instanceof FormData)) {
      // Only stringify and set Content-Type for non-FormData objects
      if (typeof options.body === 'object') {
        options.body = JSON.stringify(options.body);
        // Only set Content-Type for JSON if not explicitly set to avoid it
        if (!headers['Content-Type']) {
          headers['Content-Type'] = 'application/json';
        }
      }
    } else {
      console.log('FormData detected, letting browser set Content-Type with boundary');
    }
  }
  
  // Add authentication token if available
  const token = localStorage.getItem('token');
  if (token) {
    headers['Authorization'] = `Bearer ${token}`;
    // Log the auth token (masked for security) for debugging
    const maskedToken = token.length > 10 
      ? `${token.substring(0, 5)}...${token.substring(token.length - 5)}`
      : '***masked***';
    console.log(`Adding auth token to request: Bearer ${maskedToken}`);
  } else {
    console.log('No auth token available');
  }
  
  console.log('Request options:', {
    method: options.method || 'GET',
    hasBody: !!options.body,
    contentType: headers['Content-Type'],
    isFormData: options.body instanceof FormData
  });
  
  try {
    // Create a timeout promise
    const timeoutPromise = new Promise<never>((_, reject) => {
      setTimeout(() => reject(new Error('Request timeout')), API_TIMEOUT);
    });
    
    // Race the fetch against the timeout
    const response = await Promise.race([
      fetch(url, options),
      timeoutPromise
    ]) as Response;
    
    console.log(`Response status: ${response.status} ${response.statusText}`);
    
    // Handle non-2xx responses
    if (!response.ok) {
      console.error(`API error response: ${response.status} ${response.statusText}`);
      
      // Check if we can parse error as JSON
      try {
        const errorData = await response.json();
        console.error('API error data:', errorData);
        
        // Check if error is from the API response
        if (errorData && errorData.error) {
          throw new Error(errorData.error);
        } else if (errorData && errorData.message) {
          throw new Error(errorData.message);
        } else {
          throw new Error(`API Error: ${response.status} - ${response.statusText}`);
        }
      } catch (parseError) {
        // Couldn't parse JSON error, use status text instead
        console.error('Could not parse error response as JSON:', parseError);
        throw new Error(`API Error: ${response.status} - ${response.statusText}`);
      }
    }
    
    // Try to parse response as JSON
    try {
      const jsonResponse = await response.json();
      console.log('API response parsed successfully');
      return jsonResponse;
    } catch (parseError) {
      console.error('Error parsing response as JSON:', parseError);
      
      // Check if response is empty
      const text = await response.text();
      if (!text) {
        console.log('Empty response received');
        return {};
      }
      
      throw new Error('Invalid JSON response from API');
    }
  } catch (error) {
    console.error('API request failed:', error);
    
    // If mock response is provided and we're in development, use it as fallback
    if (mockResponse !== undefined && import.meta.env.DEV) {
      console.warn('Using mock response due to API unavailability:', mockResponse);
      return mockResponse;
    }
    
    // Log the error for debugging
    console.error(`Failed API call to ${url}:`, error);
    
    // Check if it's a network error (ECONNREFUSED, ENETUNREACH, etc.)
    if (error instanceof Error && 
        (error.message.includes('Failed to fetch') || 
         error.message.includes('Network Error') ||
         error.message.includes('net::ERR') ||
         error.message.includes('ECONNREFUSED') ||
         error.message.includes('ENETUNREACH'))) {
      console.error('Network connectivity issue detected. API server may be unreachable.');
      
      // In development, provide a more helpful error message
      if (import.meta.env.DEV) {
        console.log('Check that your API server is running and the URL is correct.');
        console.log('Current API URL:', API_BASE_URL);
      }
    }
    
    throw error;
  }
}

// API client with typed methods
export const api = {
  // GET request with query params
  get: async <T>(endpoint: string, options: { params?: Record<string, any> } = {}): Promise<T> => {
    try {
      const url = new URL(API_BASE_URL + (endpoint.startsWith('/') ? endpoint : `/${endpoint}`), window.location.origin);
      console.log(`API GET request to: ${url.toString()}`);
      
      if (options.params) {
        Object.entries(options.params).forEach(([key, value]) => {
          if (value !== undefined) url.searchParams.append(key, String(value));
        });
      }
      
      // Get the path with query parameters
      const pathWithQuery = url.pathname + url.search;
      console.log(`Sending GET request to: ${pathWithQuery}`);
      
      const response = await fetchApi<T>(pathWithQuery);
      console.log(`API GET response for ${endpoint}:`, response);
      
      if (response && response.data) {
        return response.data;
      } else if (response) {
        return response as unknown as T;
      } else {
        throw new Error('Empty response from API');
      }
    } catch (error) {
      console.error(`API GET error for endpoint ${endpoint}:`, error);
      throw error;
    }
  },

  // POST request with JSON body
  post: async <T>(endpoint: string, data?: any): Promise<T> => {
    const response = await fetchApi<T>(endpoint, {
      method: 'POST',
      body: data ? JSON.stringify(data) : undefined,
    });
    return response.data;
  },

  // POST request with FormData (for file uploads)
  upload: async <T>(endpoint: string, formData: FormData): Promise<T> => {
    try {
      console.log(`Making upload request to: ${endpoint}`);
      
      // We don't stringify FormData or set Content-Type (browser sets it with boundary)
      const response = await fetchApi<T>(endpoint, {
        method: 'POST',
        body: formData,
        // Don't add Content-Type header, browser will set it with the correct boundary
      });
      
      console.log(`Upload response for ${endpoint}:`, response);
      
      if (response && response.data) {
        return response.data;
      } else if (response) {
        return response as unknown as T;
      } else {
        throw new Error('Empty response from API');
      }
    } catch (error) {
      console.error(`Upload error for endpoint ${endpoint}:`, error);
      throw error;
    }
  },

  // Auth endpoints
  auth: {
    signIn: async (credentials: { email: string, password: string }) => {
      try {
        const response = await fetchApi<{
          success: boolean;
          data: {
            user: User;
            access_token: string;
            token_type: string;
            expires_in: number;
          }
        }>('/auth/login', { 
          method: 'POST', 
          body: JSON.stringify(credentials),
          headers: {
            'Content-Type': 'application/json'
          }
        });
        return response;
      } catch (error) {
        console.error('Sign in error:', error);
        throw error;
      }
    },
    
    signUp: async (userData: { name: string, email: string, password: string }) => {
      try {
        const response = await fetchApi<{ user: User }>('auth/register', { 
          method: 'POST', 
          body: JSON.stringify(userData),
          headers: {
            'Content-Type': 'application/json'
          }
        });
        return response.user;
      } catch (error) {
        console.error('Sign up error:', error);
        throw error;
      }
    },
    
    signOut: async () => {
      try {
        await fetchApi('/logout', { method: 'POST' });
        // Remove token and user info from localStorage
        localStorage.removeItem('token');
        localStorage.removeItem('session');
        return true;
      } catch (error) {
        console.error('Sign out error:', error);
        // Still remove local data even if API call fails
        localStorage.removeItem('token');
        localStorage.removeItem('session');
        return true;
      }
    },
    
    forgotPassword: async (email: string) => {
      try {
        return await fetchApi<{ message: string }>('/forgot-password', { 
          method: 'POST', 
          body: JSON.stringify({ email }),
          headers: {
            'Content-Type': 'application/json'
          }
        });
      } catch (error) {
        console.error('Forgot password error:', error);
        throw error;
      }
    },
    
    resetPassword: async (token: string, password: string) => {
      try {
        return await fetchApi<{ message: string }>('/reset-password', { 
          method: 'POST', 
          body: JSON.stringify({ token, password }),
          headers: {
            'Content-Type': 'application/json'
          }
        });
      } catch (error) {
        console.error('Reset password error:', error);
        throw error;
      }
    },
  },

  // Causes endpoints
  causes: {
    list: (page: number = 1, limit: number = 10, filters: Record<string, any> = {}) => {
      const params = new URLSearchParams({
        page: page.toString(),
        per_page: limit.toString(),
        ...filters
      });
      
      console.log(`API: Fetching causes with params: ${params.toString()}`);
      
      return fetchApi<{
        data: Array<{
          id: string;
          type: string | null;
          attributes: {
            title: string;
            slug: string;
            description: string;
            image: string | null;
            goal_amount: number;
            raised_amount: number;
            progress_percentage?: number;
            donors_count?: number;
            donor_count?: number;
            unique_donors?: number;
            start_date: string | null;
            end_date: string | null;
            status: string;
            category_id: string;
            created_at: string;
            updated_at: string;
            category: {
              id: string;
              name: string;
              slug: string;
            }
          }
        }>;
        meta: {
          pagination: {
            total: number;
            count: number;
            per_page: number;
            current_page: number;
            total_pages: number;
          }
        };
        links: {
          self: string;
          first: string;
          last: string;
          next?: string;
          prev?: string;
        }
      }>(`/causes?${params.toString()}`)
        .then(response => {
          console.log('API response for causes:', response);
          
          // Return the properly formatted causes from the JSON:API structure
          if (response && Array.isArray(response.data)) {
            // Map the data to a more convenient format for frontend use
            return response.data.map((item: {
              id: string;
              type: string | null;
              attributes: {
                title: string;
                slug: string;
                description: string;
                image: string | null;
                goal_amount: number;
                raised_amount: number;
                progress_percentage?: number;
                donors_count?: number;
                donor_count?: number;
                unique_donors?: number;
                start_date: string | null;
                end_date: string | null;
                status: string;
                category_id: string;
                created_at: string;
                updated_at: string;
                category: {
                  id: string;
                  name: string;
                  slug: string;
                }
              }
            }) => ({
              id: item.id,
              title: item.attributes.title,
              description: item.attributes.description,
              slug: item.attributes.slug,
              imageUrl: item.attributes.image,
              goalAmount: item.attributes.goal_amount,
              raisedAmount: item.attributes.raised_amount,
              progress_percentage: item.attributes.progress_percentage || 
                (item.attributes.raised_amount === 0 ? 0 : Math.min(Math.round((item.attributes.raised_amount / (item.attributes.goal_amount || 1)) * 100), 100)),
              donorCount: item.attributes.donors_count || item.attributes.donor_count || 0,
              donor_count: item.attributes.donors_count || item.attributes.donor_count || 0,
              donors_count: item.attributes.donors_count || 0,
              unique_donors: item.attributes.unique_donors || item.attributes.donors_count || 0,
              startDate: item.attributes.start_date,
              endDate: item.attributes.end_date,
              status: item.attributes.status,
              categoryId: item.attributes.category_id,
              createdAt: item.attributes.created_at,
              updatedAt: item.attributes.updated_at,
              category: item.attributes.category
            }));
          }
          // Fallback to empty array
          return [];
        })
        .catch(error => {
          console.error('Error fetching causes:', error);
          // Return empty array on error so the UI can handle it gracefully
          return [];
        });
    },
    getFeatured: () => 
      fetchApi<{
        data: Array<{
          id: string;
          type: string | null;
          attributes: {
            title: string;
            slug: string;
            description: string;
            image: string | null;
            goal_amount: number;
            raised_amount: number;
            progress_percentage?: number;
            donors_count?: number;
            donor_count?: number;
            unique_donors?: number;
            start_date: string | null;
            end_date: string | null;
            status: string;
            category_id: string;
            created_at: string;
            updated_at: string;
            category: {
              id: string;
              name: string;
              slug: string;
            }
          }
        }>;
      }>('/causes?is_featured=true')
        .then(response => {
          if (response && Array.isArray(response.data)) {
            return response.data.map((item: {
              id: string;
              type: string | null;
              attributes: {
                title: string;
                slug: string;
                description: string;
                image: string | null;
                goal_amount: number;
                raised_amount: number;
                progress_percentage?: number;
                donors_count?: number;
                donor_count?: number;
                unique_donors?: number;
                start_date: string | null;
                end_date: string | null;
                status: string;
                category_id: string;
                created_at: string;
                updated_at: string;
                category: {
                  id: string;
                  name: string;
                  slug: string;
                }
              }
            }) => ({
              id: item.id,
              title: item.attributes.title,
              description: item.attributes.description,
              slug: item.attributes.slug,
              imageUrl: item.attributes.image,
              goalAmount: item.attributes.goal_amount,
              raisedAmount: item.attributes.raised_amount,
              progress_percentage: item.attributes.progress_percentage || 
                (item.attributes.raised_amount === 0 ? 0 : Math.min(Math.round((item.attributes.raised_amount / (item.attributes.goal_amount || 1)) * 100), 100)),
              donorCount: item.attributes.donors_count || item.attributes.donor_count || 0,
              donor_count: item.attributes.donors_count || item.attributes.donor_count || 0,
              donors_count: item.attributes.donors_count || 0,
              unique_donors: item.attributes.unique_donors || item.attributes.donors_count || 0,
              startDate: item.attributes.start_date,
              endDate: item.attributes.end_date,
              status: item.attributes.status,
              categoryId: item.attributes.category_id,
              createdAt: item.attributes.created_at,
              updatedAt: item.attributes.updated_at,
              category: item.attributes.category
            }));
          }
          return [];
        })
        .catch(error => {
          console.error('Error fetching featured causes:', error);
          return [];
        }),
    getById: (id: string) => 
      fetchApi<{
        data: {
          id: string;
          type: string | null;
          attributes: {
            title: string;
            slug: string;
            description: string;
            image: string | null;
            goal_amount: number;
            raised_amount: number;
            progress_percentage?: number;
            donors_count?: number;
            donor_count?: number;
            unique_donors?: number;
            start_date: string | null;
            end_date: string | null;
            status: string;
            category_id: string;
            created_at: string;
            updated_at: string;
            category: {
              id: string;
              name: string;
              slug: string;
            }
          }
        }
      }>(`/causes/${id}`)
        .then(response => {
          if (response && response.data) {
            const item = response.data;
            return {
              id: item.id,
              title: item.attributes.title,
              description: item.attributes.description,
              slug: item.attributes.slug,
              imageUrl: item.attributes.image,
              goalAmount: item.attributes.goal_amount,
              raisedAmount: item.attributes.raised_amount,
              progress_percentage: item.attributes.progress_percentage || 
                (item.attributes.raised_amount === 0 ? 0 : Math.min(Math.round((item.attributes.raised_amount / (item.attributes.goal_amount || 1)) * 100), 100)),
              donorCount: item.attributes.donors_count || item.attributes.donor_count || 0,
              donor_count: item.attributes.donors_count || item.attributes.donor_count || 0,
              donors_count: item.attributes.donors_count || 0,
              unique_donors: item.attributes.unique_donors || item.attributes.donors_count || 0,
              startDate: item.attributes.start_date,
              endDate: item.attributes.end_date,
              status: item.attributes.status,
              categoryId: item.attributes.category_id,
              createdAt: item.attributes.created_at,
              updatedAt: item.attributes.updated_at,
              category: item.attributes.category
            };
          }
          throw new Error('Cause not found');
        }),
    create: (causeData: Partial<Cause>) => 
      fetchApi<Cause | { data: Cause }>('/causes', { 
        method: 'POST', 
        body: JSON.stringify(causeData) 
      }).then(res => ('data' in res) ? res.data : res),
    update: (id: string, causeData: Partial<Cause>) => 
      fetchApi<Cause | { data: Cause }>(`/causes/${id}`, { 
        method: 'PUT', 
        body: JSON.stringify(causeData) 
      }).then(res => ('data' in res) ? res.data : res),
    delete: (id: string) => fetchApi<{ message: string }>(`/causes/${id}`, { method: 'DELETE' }),
  },

  // Categories endpoint
  categories: {
    list: () => 
      fetchApi<any[] | { data: any[] }>('/categories')
        .then(res => Array.isArray(res) ? res : res.data || []),
    getById: (id: string) => 
      fetchApi<any | { data: any }>(`/categories/${id}`)
        .then(res => ('data' in res) ? res.data : res),
  },

  // Profile endpoints
  profile: {
    get: async () => {
      try {
        const response = await fetchApi<{ data: User }>('/user');
        return response.data || response;
      } catch (error) {
        console.error('Error fetching profile:', error);
        throw error;
      }
    },
    update: async (data: any) => {
      try {
        const response = await fetchApi<{ data: User }>('/user', {
          method: 'PUT',
          body: JSON.stringify(data)
        });
        return response.data || response;
      } catch (error) {
        console.error('Error updating profile:', error);
        throw error;
      }
    },
    uploadAvatar: async (file: File) => {
      try {
        // Create a FormData instance for file upload
        const formData = new FormData();
        formData.append('file', file);
        
        // Important: Don't set Content-Type header - browser will set it with boundary
        // Let the browser handle the Content-Type header for multipart/form-data
        const response = await fetchApi<{ success: boolean, url: string }>('/upload/avatar', {
          method: 'POST',
          body: formData,
          // Explicitly avoid Content-Type header being set in fetchApi
          headers: {
            // Empty object to prevent default headers in fetchApi
          }
        });
        
        console.log('Avatar upload response:', response);
        return response;
      } catch (error) {
        console.error('Error uploading avatar:', error);
        throw error;
      }
    }
  },

  // Payments endpoint
  payments: {
    process: async (paymentData: any) => {
      try {
        const response = await fetchApi<any>('/payments/process', {
          method: 'POST',
          body: JSON.stringify(paymentData)
        });
        console.log('Payment processed:', response);
        return response.data || response;
      } catch (error) {
        console.error('Payment processing failed:', error);
        
        // Save to local storage as backup when API fails
        console.log('Saving payment to local database instead');
        const localPayment = saveToLocalDatabase('payments', {
          ...paymentData,
          status: 'failed',
          savedLocally: true,
          saveError: error instanceof Error ? error.message : String(error),
          timestamp: new Date().toISOString()
        });
        
        throw error;
      }
    },
    
    checkStatus: async (paymentId: string) => {
      try {
        const response = await fetchApi<any>(`/payments/${paymentId}/status`);
        return response.data || response;
      } catch (error) {
        console.error(`Failed to check payment status for ID ${paymentId}:`, error);
        throw error;
      }
    },
    
    methods: async (amount: number | null = null, currency: string | null = null) => {
      try {
        // Construct URL with query parameters if provided
        let url = '/payment-methods';
        const params: string[] = [];
        
        if (amount !== null) params.push(`amount=${amount}`);
        if (currency !== null) params.push(`currency=${currency}`);
        
        if (params.length > 0) {
          url += `?${params.join('&')}`;
        }
        
        console.log(`Fetching payment methods: ${url}`);
        
        const response = await fetchApi<any>(url);
        console.log('Payment methods response:', response);
        
        if (response && response.success && response.data && response.data.payment_methods) {
          console.log('Using nested payment_methods array from response');
          return response.data.payment_methods;
        } else if (response && response.data && Array.isArray(response.data)) {
          console.log('Using array directly from response.data');
          return response.data;
        } else if (response && Array.isArray(response)) {
          console.log('Using array directly from response');
          return response;
        } else {
          console.error('Unexpected response format:', response);
          return [];
        }
      } catch (error) {
        console.error('Error fetching payment methods:', error);
        return [];
      }
    },
  },

  // Donations endpoints
  donations: {
    create: async (donationData: any) => {
      try {
        // Try to save to remote DB first
        const response = await fetchApi<any | { data: any }>('/donations', { 
          method: 'POST', 
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
          },
          body: JSON.stringify(donationData) 
        });
        console.log('Donation saved to remote database:', response);
        return ('data' in response) ? response.data : response;
      } catch (error) {
        console.error('Failed to save donation to remote DB:', error);
        
        // Save to local storage as backup when API fails
        console.log('Saving donation to local database instead');
        const localDonation = saveToLocalDatabase('donations', {
          ...donationData,
          status: 'completed',
          savedLocally: true,
          saveError: error instanceof Error ? error.message : String(error),
          timestamp: new Date().toISOString()
        });
        
        // Return the locally saved donation
        return localDonation;
      }
    },
    getUserDonations: async () => {
      try {
        // Get the current authenticated user
        const userString = localStorage.getItem('session');
        
        // Debug: Check localStorage keys
        console.log('All localStorage keys:', Object.keys(localStorage));
        console.log('session from localStorage:', userString);
        
        let userId = '';
        
        if (userString) {
          try {
            const user = JSON.parse(userString);
            userId = user.id;
            console.log('Found user ID from localStorage:', userId);
          } catch (e) {
            console.error('Failed to parse user from localStorage', e);
          }
        }
        
        if (!userId) {
          console.warn('No user ID available, cannot fetch user-specific donations');
          return [];
        }
        
        // Use the /donations endpoint directly, which should return current user's donations
        // when authenticated with the Bearer token
        console.log(`Fetching donations for user ID: ${userId}`);
        const endpoint = '/donations';
        console.log(`API endpoint: ${endpoint}`);
        console.log(`Full URL will be: ${API_BASE_URL}${endpoint}`);
        const response = await fetchApi<any>(endpoint);
        
        console.log('API response for donations (RAW):', JSON.stringify(response, null, 2));
        console.log('API response for donations:', response);
        
        // Return the response directly, the useDonations hook will handle the processing
        return response;
      } catch (error) {
        console.error('Failed to get user donations:', error);
        
        // Return donations from local storage as fallback
        const db = getLocalDatabase();
        const localDonations = db.donations || [];
        
        // If there are local donations, use them
        if (localDonations.length > 0) {
          console.log('Found donations in local storage:', localDonations);
          return localDonations;
        }
        
        // Re-throw the error if we have no local fallback
        throw error;
      }
    },
    // Get a single donation by ID
    getById: async (donationId: string) => {
      try {
        const response = await fetchApi<any | { data: any }>(`/donations/${donationId}`);
        return ('data' in response) ? response.data : response;
      } catch (error) {
        console.error(`Failed to get donation with ID ${donationId}:`, error);
        throw error;
      }
    },
    // Update a donation status
    updateStatus: async (donationId: string, status: string) => {
      try {
        const response = await fetchApi<any | { data: any }>(`/donations/${donationId}`, {
          method: 'PATCH',
          body: JSON.stringify({ status })
        });
        return ('data' in response) ? response.data : response;
      } catch (error) {
        console.error(`Failed to update donation status for ID ${donationId}:`, error);
        throw error;
      }
    }
  },

  // Achievements endpoint
  achievements: {
    list: async () => {
      try {
        console.log('Fetching user achievements');
        
        // Get the current user ID
        const userString = localStorage.getItem('session');
        let userId = '';
        
        if (userString) {
          try {
            const user = JSON.parse(userString);
            userId = user.id;
            console.log('Found user ID for achievements:', userId);
          } catch (e) {
            console.error('Failed to parse user from localStorage', e);
          }
        }
        
        // Use the achievements endpoint
        const endpoint = '/achievements';
        console.log(`Using endpoint: ${endpoint}`);
        
        const response = await fetchApi<any>(endpoint);
        
        console.log('Achievements API response (raw):', JSON.stringify(response, null, 2));
        console.log('Achievements API response:', response);
        
        // Return the response directly so the hook can process it
        return response;
      } catch (error) {
        console.error('Error fetching achievements:', error);
        return [];
      }
    },
  },

  partners: {
    list: async () => {
      try {
        const response = await fetchApi<{ data: any[] }>('/partners');
        return response?.data || [];
      } catch (error) {
        console.error('Error fetching partners:', error);
        return [];
      }
    },
  },
};