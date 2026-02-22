import { API_BASE_URL } from './config';

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

// Shared type for raw JSON:API cause item from API
interface ApiCauseItem {
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
    };
  };
}

/**
 * Maps a raw JSON:API cause item to the frontend Cause shape.
 * This is the single source of truth for cause mapping - avoids duplication.
 */
function mapApiCauseToCause(item: ApiCauseItem) {
  const attrs = item.attributes;
  return {
    id: item.id,
    title: attrs.title,
    description: attrs.description,
    slug: attrs.slug,
    imageUrl: attrs.image,
    goalAmount: attrs.goal_amount,
    raisedAmount: attrs.raised_amount,
    progress_percentage:
      attrs.progress_percentage ??
      (attrs.raised_amount === 0
        ? 0
        : Math.min(
            Math.round((attrs.raised_amount / (attrs.goal_amount || 1)) * 100),
            100
          )),
    donorCount: attrs.donors_count ?? attrs.donor_count ?? 0,
    donor_count: attrs.donors_count ?? attrs.donor_count ?? 0,
    donors_count: attrs.donors_count ?? 0,
    unique_donors: attrs.unique_donors ?? attrs.donors_count ?? 0,
    startDate: attrs.start_date,
    endDate: attrs.end_date,
    status: attrs.status,
    categoryId: attrs.category_id,
    createdAt: attrs.created_at,
    updatedAt: attrs.updated_at,
    category: attrs.category,
  };
}

// Default timeout for API requests (10 seconds)
const API_TIMEOUT = 10000;

/**
 * Helper function to fetch from the API with proper error handling.
 * Callers should pass plain objects as body; this function handles JSON.stringify.
 */
export async function fetchApi<T>(
  endpoint: string,
  options: RequestInit = {},
  mockResponse?: T
): Promise<T | any> {
  // Ensure endpoint starts with a slash
  const path = endpoint.startsWith('/') ? endpoint : `/${endpoint}`;
  const url = `${API_BASE_URL}${path}`;

  // Initialize headers
  if (!options.headers) {
    options.headers = {};
  }

  const headers = options.headers as Record<string, string>;

  // Handle body serialization
  if (options.body) {
    if (!(options.body instanceof FormData)) {
      if (typeof options.body === 'object') {
        options.body = JSON.stringify(options.body);
        if (!headers['Content-Type']) {
          headers['Content-Type'] = 'application/json';
        }
      }
    }
  }

  // Add authentication token if available
  const token = localStorage.getItem('token');
  if (token) {
    headers['Authorization'] = `Bearer ${token}`;
  }

  try {
    const timeoutPromise = new Promise<never>((_, reject) => {
      setTimeout(() => reject(new Error('Request timeout')), API_TIMEOUT);
    });

    const response = await Promise.race([
      fetch(url, options),
      timeoutPromise,
    ]) as Response;

    if (!response.ok) {
      try {
        const errorData = await response.json();
        if (errorData?.error) {
          throw new Error(errorData.error);
        } else if (errorData?.message) {
          throw new Error(errorData.message);
        } else {
          throw new Error(`API Error: ${response.status} - ${response.statusText}`);
        }
      } catch (parseError) {
        if (parseError instanceof Error && parseError.message.startsWith('API Error:')) {
          throw parseError;
        }
        throw new Error(`API Error: ${response.status} - ${response.statusText}`);
      }
    }

    try {
      const jsonResponse = await response.json();
      return jsonResponse;
    } catch {
      const text = await response.text();
      if (!text) {
        return {};
      }
      throw new Error('Invalid JSON response from API');
    }
  } catch (error) {
    // If mock response is provided and we're in development, use it as fallback
    if (mockResponse !== undefined && import.meta.env.DEV) {
      return mockResponse;
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

      if (options.params) {
        Object.entries(options.params).forEach(([key, value]) => {
          if (value !== undefined) url.searchParams.append(key, String(value));
        });
      }

      const pathWithQuery = url.pathname + url.search;
      const response = await fetchApi<T>(pathWithQuery);

      if (response && response.data) {
        return response.data;
      } else if (response) {
        return response as unknown as T;
      } else {
        throw new Error('Empty response from API');
      }
    } catch (error) {
      throw error;
    }
  },

  // POST request with JSON body — callers pass plain objects, not pre-stringified JSON
  post: async <T>(endpoint: string, data?: any): Promise<T> => {
    const response = await fetchApi<T>(endpoint, {
      method: 'POST',
      body: data,
    });
    return response.data;
  },

  // POST request with FormData (for file uploads)
  upload: async <T>(endpoint: string, formData: FormData): Promise<T> => {
    try {
      const response = await fetchApi<T>(endpoint, {
        method: 'POST',
        body: formData,
      });

      if (response && response.data) {
        return response.data;
      } else if (response) {
        return response as unknown as T;
      } else {
        throw new Error('Empty response from API');
      }
    } catch (error) {
      throw error;
    }
  },

  // Auth endpoints
  auth: {
    signIn: async (credentials: { email: string; password: string }) => {
      try {
        const response = await fetchApi<{
          success: boolean;
          data: {
            user: User;
            access_token: string;
            token_type: string;
            expires_in: number;
          };
        }>('/auth/login', {
          method: 'POST',
          body: credentials,
          headers: {
            'Content-Type': 'application/json',
          },
        });
        return response;
      } catch (error) {
        throw error;
      }
    },

    signUp: async (userData: { name: string; email: string; password: string }) => {
      try {
        const response = await fetchApi<{ user: User }>('auth/register', {
          method: 'POST',
          body: userData,
          headers: {
            'Content-Type': 'application/json',
          },
        });
        return response.user;
      } catch (error) {
        throw error;
      }
    },

    signOut: async () => {
      try {
        await fetchApi('/logout', { method: 'POST' });
        localStorage.removeItem('token');
        localStorage.removeItem('session');
        return true;
      } catch {
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
          body: { email },
          headers: {
            'Content-Type': 'application/json',
          },
        });
      } catch (error) {
        throw error;
      }
    },

    resetPassword: async (token: string, password: string) => {
      try {
        return await fetchApi<{ message: string }>('/reset-password', {
          method: 'POST',
          body: { token, password },
          headers: {
            'Content-Type': 'application/json',
          },
        });
      } catch (error) {
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
        ...filters,
      });

      return fetchApi<{
        data: ApiCauseItem[];
        meta: {
          pagination: {
            total: number;
            count: number;
            per_page: number;
            current_page: number;
            total_pages: number;
          };
        };
        links: {
          self: string;
          first: string;
          last: string;
          next?: string;
          prev?: string;
        };
      }>(`/causes?${params.toString()}`)
        .then(response => {
          if (response && Array.isArray(response.data)) {
            return response.data.map(mapApiCauseToCause);
          }
          return [];
        })
        .catch(() => {
          return [];
        });
    },

    getFeatured: () =>
      fetchApi<{ data: ApiCauseItem[] }>('/causes?is_featured=true')
        .then(response => {
          if (response && Array.isArray(response.data)) {
            return response.data.map(mapApiCauseToCause);
          }
          return [];
        })
        .catch(() => {
          return [];
        }),

    getById: (id: string) =>
      fetchApi<{ data: ApiCauseItem }>(`/causes/${id}`)
        .then(response => {
          if (response && response.data) {
            return mapApiCauseToCause(response.data);
          }
          throw new Error('Cause not found');
        }),

    create: (causeData: Record<string, any>) =>
      fetchApi<any>('/causes', {
        method: 'POST',
        body: causeData,
      }).then(res => ('data' in res ? res.data : res)),

    update: (id: string, causeData: Record<string, any>) =>
      fetchApi<any>(`/causes/${id}`, {
        method: 'PUT',
        body: causeData,
      }).then(res => ('data' in res ? res.data : res)),

    delete: (id: string) =>
      fetchApi<{ message: string }>(`/causes/${id}`, { method: 'DELETE' }),
  },

  // Categories endpoint
  categories: {
    list: () =>
      fetchApi<any[] | { data: any[] }>('/categories')
        .then(res => (Array.isArray(res) ? res : res.data || [])),
    getById: (id: string) =>
      fetchApi<any | { data: any }>(`/categories/${id}`)
        .then(res => ('data' in res ? res.data : res)),
  },

  // Profile endpoints
  profile: {
    get: async () => {
      try {
        const response = await fetchApi<{ data: User }>('/user');
        return response.data || response;
      } catch (error) {
        throw error;
      }
    },
    update: async (data: any) => {
      try {
        const response = await fetchApi<{ data: User }>('/user', {
          method: 'PUT',
          body: data,
        });
        return response.data || response;
      } catch (error) {
        throw error;
      }
    },
    uploadAvatar: async (file: File) => {
      try {
        const formData = new FormData();
        formData.append('file', file);

        const response = await fetchApi<{ success: boolean; url: string }>('/upload/avatar', {
          method: 'POST',
          body: formData,
          headers: {},
        });

        return response;
      } catch (error) {
        throw error;
      }
    },
  },

  // Payments endpoint
  payments: {
    process: async (paymentData: any) => {
      try {
        const response = await fetchApi<any>('/payments/process', {
          method: 'POST',
          body: paymentData,
        });
        return response.data || response;
      } catch (error) {
        throw error;
      }
    },

    checkStatus: async (paymentId: string) => {
      try {
        const response = await fetchApi<any>(`/payments/${paymentId}/status`);
        return response.data || response;
      } catch (error) {
        throw error;
      }
    },

    methods: async (amount: number | null = null, currency: string | null = null) => {
      try {
        let url = '/payment-methods';
        const params: string[] = [];

        if (amount !== null) params.push(`amount=${amount}`);
        if (currency !== null) params.push(`currency=${currency}`);

        if (params.length > 0) {
          url += `?${params.join('&')}`;
        }

        const response = await fetchApi<any>(url);

        if (response?.success && response?.data?.payment_methods) {
          return response.data.payment_methods;
        } else if (response?.data && Array.isArray(response.data)) {
          return response.data;
        } else if (Array.isArray(response)) {
          return response;
        } else {
          return [];
        }
      } catch {
        return [];
      }
    },
  },

  // Donations endpoints
  donations: {
    create: async (donationData: any) => {
      const response = await fetchApi<any | { data: any }>('/donations', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: donationData,
      });
      return ('data' in response) ? response.data : response;
    },

    getUserDonations: async () => {
      const userString = localStorage.getItem('session');
      let userId = '';

      if (userString) {
        try {
          const user = JSON.parse(userString);
          userId = user.id;
        } catch {
          // Ignore parse errors
        }
      }

      if (!userId) {
        return [];
      }

      const endpoint = '/donations?user_id=' + userId;
      const response = await fetchApi<any>(endpoint);
      return response;
    },

    getById: async (donationId: string) => {
      try {
        const response = await fetchApi<any | { data: any }>(`/donations/${donationId}`);
        return ('data' in response) ? response.data : response;
      } catch (error) {
        throw error;
      }
    },

    updateStatus: async (donationId: string, status: string) => {
      try {
        const response = await fetchApi<any | { data: any }>(`/donations/${donationId}`, {
          method: 'PATCH',
          body: { status },
        });
        return ('data' in response) ? response.data : response;
      } catch (error) {
        throw error;
      }
    },
  },

  // Achievements endpoint
  achievements: {
    list: async () => {
      try {
        const response = await fetchApi<any>('/achievements');
        return response;
      } catch {
        return [];
      }
    },
  },

  partners: {
    list: async () => {
      try {
        const response = await fetchApi<{ data: any[] }>('/partners');
        return response?.data || [];
      } catch {
        return [];
      }
    },
  },
};
