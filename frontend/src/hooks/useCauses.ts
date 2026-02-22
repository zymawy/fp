import { useState, useEffect, useCallback } from 'react';
import { useToast } from '@/components/ui/use-toast';
import { api } from '@/lib/api';

// Define Cause interface directly in the file
export interface Cause {
  id: string;
  title: string;
  description: string;
  imageUrl?: string;
  featuredImage?: string;
  featured_image?: string;
  image_url?: string;
  image?: string;
  raisedAmount?: number;
  raised_amount?: number;
  goalAmount?: number;
  goal_amount?: number;
  target_amount?: number;
  donorCount?: number;
  donor_count?: number;
  donors_count?: number;
  unique_donors?: number;
  categoryId?: string;
  category_id?: string;
  category_name?: string;
  category?: {
    id: string | number;
    name: string;
    slug?: string;
  };
  status?: string;
  urgencyLevel?: string;
  urgency_level?: string;
  location?: string;
  progress_percentage?: number;
  startDate?: Date;
  endDate?: Date;
  featured?: boolean;
  is_featured?: boolean;
  sliderButtonText?: string;
  slider_button_text?: string;
  sliderSubtitle?: string;
  slider_subtitle?: string;
  createdAt?: string;
  updatedAt?: string;
}

interface Filters {
  categoryId?: string;
  minAmount?: string;
  maxAmount?: string;
  status?: string;
  urgencyLevel?: string;
  location?: string;
  search?: string;
}

// Increase page size to show more items per page
const PAGE_SIZE = 9;

export function useCauses() {
  const [causes, setCauses] = useState<Cause[]>([]);
  const [hasMore, setHasMore] = useState(true);
  const [page, setPage] = useState(1); // Start with page 1 for API
  const [loading, setLoading] = useState(false);
  const [initialLoading, setInitialLoading] = useState(true); // Separate initial loading state
  const [error, setError] = useState<string | null>(null);
  const [filters, setFilters] = useState<Filters>({});
  const { toast } = useToast();

  const fetchCauses = useCallback(async (pageNumber: number, isInitialLoad = false) => {
    if (isInitialLoad) {
      setInitialLoading(true);
    } else {
      setLoading(true);
    }
    setError(null);
    
    try {
      // Convert filters to API parameters
      const apiFilters: Record<string, any> = {};
      
      // Map all filters to API parameters
      if (filters.categoryId) apiFilters.category_id = filters.categoryId;
      if (filters.status) apiFilters.status = filters.status;
      if (filters.urgencyLevel) apiFilters.urgency_level = filters.urgencyLevel;
      if (filters.minAmount) apiFilters.min_amount = filters.minAmount;
      if (filters.maxAmount) apiFilters.max_amount = filters.maxAmount;
      if (filters.location) apiFilters.location = filters.location;
      if (filters.search) apiFilters.search = filters.search;
      
      // Using the API client to fetch data
      const data = await api.causes.list(pageNumber, PAGE_SIZE, apiFilters);
      
      // Handle different response formats
      let causesData: Cause[] = [];
      
      if (Array.isArray(data)) {
        causesData = data;
      } else if (data && typeof data === 'object' && 'data' in data) {
        causesData = data.data || [];
      } else if (!data) {
        causesData = [];
      } else {
        throw new Error('Received unexpected data format from the server');
      }
      
      // Update hasMore based on whether we got a full page
      setHasMore(causesData.length === PAGE_SIZE);
      
      return causesData;
    } catch (err) {
      const errorMessage = err instanceof Error ? err.message : 'Failed to load causes';
      setError(errorMessage);
      
      toast({
        title: "Error fetching causes",
        description: errorMessage,
        variant: "destructive"
      });
      
      return [];
    } finally {
      if (isInitialLoad) {
        setInitialLoading(false);
      } else {
        setLoading(false);
      }
    }
  }, [filters, toast]);

  const loadMore = useCallback(async () => {
    // Prevent concurrent loadMore calls
    if (loading || !hasMore) return;
    
    const newCauses = await fetchCauses(page);
    
    if (newCauses && newCauses.length > 0) {
      setCauses(prev => {
        // Filter out duplicates
        const filteredNewCauses = newCauses.filter(
          newCause => !prev.some(existingCause => existingCause.id === newCause.id)
        );
        return [...prev, ...filteredNewCauses];
      });
      setPage(prev => prev + 1);
    } else {
      setHasMore(false);
    }
  }, [fetchCauses, hasMore, loading, page]);

  const updateFilters = useCallback((newFilters: Filters) => {
    setFilters(newFilters);
    setPage(1); // Reset to first page
    setCauses([]);
    setHasMore(true);
  }, []);
  
  const refetch = useCallback(async () => {
    setPage(1);
    setCauses([]);
    setHasMore(true);
    
    const initialData = await fetchCauses(1, true);
    setCauses(initialData || []);
    setPage(2);
  }, [fetchCauses]);

  // Load initial data when filters change
  useEffect(() => {
    const loadInitialData = async () => {
      try {
        const initialData = await fetchCauses(1, true);
        setCauses(initialData || []);
        setPage(2); // Next page would be 2
      } catch {
        // Error handled by fetchCauses which sets error state
      }
    };
    
    loadInitialData();
  }, [fetchCauses, filters]);

  return {
    causes,
    hasMore,
    loading,
    initialLoading,
    error,
    loadMore,
    updateFilters,
    refetch,
    filters
  };
}