import { useState, useEffect } from 'react';
import { useAuth } from './useAuth';
import { api } from '@/lib/api';

interface Achievement {
  id: string;
  achieved_at: string;
  achievement_type: {
    id: string;
    title: string;
    description: string;
    icon: string;
  };
}

export function useAchievements() {
  const [achievements, setAchievements] = useState<Achievement[]>([]);
  const [loading, setLoading] = useState(true);
  const { user } = useAuth();

  useEffect(() => {
    const fetchAchievements = async () => {
      if (!user) {
        setLoading(false);
        return;
      }

      try {
        const data = await api.get<Achievement[]>(`users/${user.id}/achievements`);
        setAchievements(Array.isArray(data) ? data : []);
        setLoading(false);
      } catch (error) {
        console.error('Error fetching achievements:', error);
        setAchievements([]);
        setLoading(false);
      }
    };

    fetchAchievements();
  }, [user]);

  return { achievements, loading };
}