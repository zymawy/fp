import { useState, useEffect } from 'react';
import { useAuth } from './useAuth';
import { api } from '@/lib/api';
import { useToast } from '@/components/ui/use-toast';

export interface Donation {
  id: string;
  amount: number;
  created_at: string;
  cause: {
    title: string;
    id?: string;
  };
  status: 'completed' | 'pending' | 'failed';
}

// Interface for JSON:API relationship
interface Relationship {
  data: {
    id: string;
    type: string;
  };
}

// Interface for JSON:API included resource
interface IncludedResource {
  id: string;
  type: string;
  attributes: Record<string, any>;
}

export function useDonations() {
  const [donations, setDonations] = useState<Donation[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const { user } = useAuth();
  const { toast } = useToast();

  useEffect(() => {
    const fetchDonations = async () => {
      // Clear any previous errors
      setError(null);
      
      if (!user) {
        setLoading(false);
        return;
      }

      try {
        console.log('Fetching donations for user:', user?.id);
        
        // Use the API client to fetch donations
        const data = await api.donations.getUserDonations();
        
        console.log('Raw donations response:', data);
        console.log('Donations response type:', typeof data);
        console.log('Is array?', Array.isArray(data));
        if (typeof data === 'object' && data !== null) {
          console.log('Object keys:', Object.keys(data));
          if ('data' in data) {
            console.log('Has data property, data type:', typeof data.data);
            console.log('Is data an array?', Array.isArray(data.data));
          }
          if ('included' in data) {
            console.log('Has included property');
          }
        }
        
        console.log('Donations fetched:', data);
        
        // Check if data is in JSON:API format with included resources
        if (data && typeof data === 'object' && 'data' in data && Array.isArray(data.data)) {
          // Extract donations from data array
          const donationsData = data.data;
          // Extract included resources (causes, etc.)
          const included = Array.isArray(data.included) ? data.included : [];
          
          // Create a map of included resources by type and id for easy lookup
          const includedMap = new Map<string, IncludedResource>();
          included.forEach((resource: IncludedResource) => {
            includedMap.set(`${resource.type}:${resource.id}`, resource);
          });
          
          // Process each donation and associate with its cause
          setDonations(donationsData.map((donation: any) => {
            // Get the donation attributes
            const attrs = donation.attributes || donation;
            
            // Find the cause relationship
            let causeTitle = 'Unknown Cause';
            let causeId = '';
            
            // Try to get cause from relationships if present
            if (donation.relationships?.cause?.data) {
              const causeRef = donation.relationships.cause.data;
              const causeResource = includedMap.get(`${causeRef.type}:${causeRef.id}`);
              
              if (causeResource) {
                causeTitle = causeResource.attributes.title || 'Unknown Cause';
                causeId = causeRef.id;
              }
            } 
            // Try direct cause reference if available
            else if (attrs.cause_id && included.length > 0) {
              // Try to find the cause by id in included resources
              const causeResource = included.find(
                (resource: any) => resource.type === 'causes' && resource.id === attrs.cause_id
              );
              
              if (causeResource) {
                causeTitle = causeResource.attributes.title || 'Unknown Cause';
                causeId = causeResource.id;
              }
            }
            // Use cause object if directly nested in the donation
            else if (attrs.cause && attrs.cause.title) {
              causeTitle = attrs.cause.title;
              causeId = attrs.cause.id || '';
            }
            
            return {
              id: donation.id || attrs.id,
              amount: Number(attrs.amount),
              created_at: attrs.created_at || attrs.createdAt || new Date().toISOString(),
              cause: {
                title: causeTitle,
                id: causeId
              },
              status: attrs.payment_status || attrs.status || 'completed',
            };
          }));
        } 
        // Handle array response format
        else if (Array.isArray(data) && data.length > 0) {
          setDonations(data.map((donation: any) => ({
            id: donation.id,
            amount: Number(donation.amount),
            created_at: donation.createdAt || donation.created_at,
            cause: {
              title: donation.cause?.title || 'Unknown Cause',
              id: donation.cause?.id || donation.cause_id || '',
            },
            status: donation.status || donation.payment_status || 'completed',
          })));
        } else {
          console.log('No donations found or unexpected format:', data);
          setDonations([]);
        }
      } catch (error) {
        const errorMsg = error instanceof Error ? error.message : String(error);
        console.error('Error fetching donations:', errorMsg);
        setError(`Error fetching donations: ${errorMsg}`);
        
        toast({
          title: "Error",
          description: "Failed to fetch your donations. Please try again later.",
          variant: "destructive",
        });
      } finally {
        setLoading(false);
      }
    };

    if (user) {
      fetchDonations();
      
      // Set up polling for updates (only if user is logged in)
      const interval = setInterval(fetchDonations, 30000);
      
      return () => {
        clearInterval(interval);
      };
    } else {
      setLoading(false);
    }
  }, [user, toast]);

  return { donations, loading, error, refetch: () => setLoading(true) };
}