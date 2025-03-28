import { useState, useEffect } from 'react';
import { useAuth } from './useAuth';
import { api } from '@/lib/api';

interface Statistics {
  totalDonated: number;
  donationCount: number;
  achievementCount: number;
  loading: boolean;
}

interface ApiResponse {
  success?: boolean;
  data?: {
    totalDonated: number;
    donationCount: number;
    achievementCount: number;
  };
  totalDonated?: number;
  donationCount?: number;
  achievementCount?: number;
}

export function useStatistics() {
  const [statistics, setStatistics] = useState<Statistics>({
    totalDonated: 0,
    donationCount: 0,
    achievementCount: 0,
    loading: true,
  });

  const { user } = useAuth();

  useEffect(() => {
    const fetchStatistics = async () => {
      if (!user) {
        setStatistics(prev => ({ ...prev, loading: false }));
        return;
      }

      try {
        // Fix: Remove the /api prefix to avoid duplication and remove leading /
        const response = await api.get<ApiResponse>(`users/${user.id}/statistics`);
        
        console.log('Statistics response:', response);
        
        if (response && typeof response === 'object') {
          // Handle case where we get direct data object
          if ('totalDonated' in response && 'donationCount' in response && 'achievementCount' in response) {
            setStatistics({
              totalDonated: Number(response.totalDonated) || 0,
              donationCount: Number(response.donationCount) || 0,
              achievementCount: Number(response.achievementCount) || 0,
              loading: false
            });
          } 
          // Handle case where we get data nested in a data object
          else if (response.data && typeof response.data === 'object') {
            setStatistics({
              totalDonated: Number(response.data.totalDonated) || 0,
              donationCount: Number(response.data.donationCount) || 0,
              achievementCount: Number(response.data.achievementCount) || 0,
              loading: false
            });
          } else {
            console.error('Invalid statistics response format:', response);
            setStatistics(prev => ({ ...prev, loading: false }));
          }
        } else {
          console.error('Empty or invalid statistics response:', response);
          setStatistics(prev => ({ ...prev, loading: false }));
        }
      } catch (error) {
        console.error('Error fetching statistics:', error);
        // Just set loading to false, keep the zero values for stats
        setStatistics(prev => ({ ...prev, loading: false }));
      }
    };

    fetchStatistics();
  }, [user]);

  return statistics;
}