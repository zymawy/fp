import { useState, useEffect } from 'react';
import { useToast } from '@/components/ui/use-toast';
import { api } from '@/lib/api';
import type { Cause } from '@/lib/db';

interface Filters {
  categoryId?: string;
  minAmount?: string;
  maxAmount?: string;
  status?: string;
  urgencyLevel?: string;
  location?: string;
  search?: string;
}

const PAGE_SIZE = 6;

export function useCauses() {
  const [causes, setCauses] = useState<Cause[]>([]);
  const [hasMore, setHasMore] = useState(true);
  const [page, setPage] = useState(1); // Start with page 1 for API
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [filters, setFilters] = useState<Filters>({});
  const { toast } = useToast();

  const fetchCauses = async (pageNumber: number) => {
    setLoading(true);
    setError(null);
    
    try {
      console.log('Fetching causes for page:', pageNumber, 'with filters:', filters);
      
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
      console.log('Received causes data:', data);
      
      // Handle different response formats
      let causesData: Cause[] = [];
      
      if (Array.isArray(data)) {
        causesData = data;
      } else if (data && typeof data === 'object' && 'data' in data) {
        causesData = data.data || [];
      } else if (!data) {
        causesData = [];
      } else {
        console.error('Unexpected API response format:', data);
        throw new Error('Received unexpected data format from the server');
      }
      
      // Map Laravel API response to the Cause type
      const mappedCauses = causesData.map((cause: any) => ({
        id: cause.id,
        title: cause.title,
        description: cause.description,
        imageUrl: cause.featured_image || cause.image_url,
        raisedAmount: parseFloat(cause.current_amount || cause.raised_amount || 0),
        goalAmount: parseFloat(cause.goal_amount || 0),
        donorCount: cause.donors_count || cause.donor_count || 0,
        categoryId: cause.category_id,
        status: cause.status || 'active',
        urgencyLevel: cause.urgency_level || 'medium',
        location: cause.location || '',
        category: cause.category ? {
          id: cause.category.id,
          name: cause.category.name,
          slug: cause.category.slug
        } : undefined,
        startDate: cause.start_date ? new Date(cause.start_date) : undefined,
        endDate: cause.end_date ? new Date(cause.end_date) : undefined,
        featured: cause.is_featured || cause.featured || false,
        sliderButtonText: cause.slider_button_text,
        sliderSubtitle: cause.slider_subtitle
      }));
      
      // Update hasMore based on whether we got a full page
      setHasMore(mappedCauses.length === PAGE_SIZE);
      
      return mappedCauses;
    } catch (err) {
      console.error('Error fetching causes:', err);
      const errorMessage = err instanceof Error ? err.message : 'Failed to load causes';
      setError(errorMessage);
      
      toast({
        title: "Error fetching causes",
        description: errorMessage,
        variant: "destructive"
      });
      
      return [];
    } finally {
      setLoading(false);
    }
  };

  const loadMore = async () => {
    if (loading) return;
    
    const newCauses = await fetchCauses(page);
    if (newCauses && newCauses.length > 0) {
      setCauses(prev => [...prev, ...newCauses.filter((cause: Cause) => 
        !prev.some(p => p.id === cause.id)
      )]);
      setPage(prev => prev + 1);
    }
  };

  const updateFilters = (newFilters: Filters) => {
    console.log('Updating filters:', newFilters);
    setFilters(newFilters);
    setPage(1); // Reset to first page
    setCauses([]);
    setHasMore(true);
  };
  
  const refetch = async () => {
    setPage(1);
    setCauses([]);
    setHasMore(true);
    
    const initialData = await fetchCauses(1);
    setCauses(initialData || []);
    setPage(2);
  };

  useEffect(() => {
    // Initial data load
    const loadInitialData = async () => {
      try {
        console.log('Fetching initial causes data...');
        const initialData = await fetchCauses(1);
        console.log('Received causes data:', initialData);
        setCauses(initialData || []);
        setPage(2); // Next page would be 2
      } catch (error) {
        console.error('Error loading initial causes:', error);
      }
    };
    
    loadInitialData();
  }, [filters]); // Reload when filters change

  return {
    causes,
    hasMore,
    loading,
    error,
    loadMore,
    updateFilters,
    refetch,
    filters
  };
}