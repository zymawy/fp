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

// Interface for the JSON:API included resource
interface IncludedResource {
  type: string | null;
  id: string;
  attributes: {
    title: string;
    description: string;
    icon: string;
    [key: string]: any;
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
        console.log('Fetching achievements for user:', user.id);
        
        // Use the API client to fetch achievements with proper endpoint
        const data = await api.achievements.list();
        
        console.log('Raw achievements response:', data);
        
        // Handle the JSON:API format with included resources
        if (data && typeof data === 'object' && 'data' in data && Array.isArray(data.data)) {
          console.log('Processing achievements JSON:API format');
          
          // Create a map of included resources by id for easy lookup
          const includedMap = new Map<string, IncludedResource>();
          
          // Check if included array exists in the response
          if (Array.isArray(data.included)) {
            data.included.forEach((resource: IncludedResource) => {
              includedMap.set(resource.id, resource);
            });
          }
          
          // Process the achievements data
          const achievementsData = data.data.map((item: any) => {
            const attrs = item.attributes || {};
            
            // Initialize default achievement type data
            let achievementTypeData = {
              id: '',
              title: 'Unknown Achievement',
              description: '',
              icon: 'Trophy'
            };
            
            // Try to find the achievement type in included resources
            if (item.relationships?.achievementType?.data?.id) {
              const typeId = item.relationships.achievementType.data.id;
              const includedType = includedMap.get(typeId);
              
              if (includedType) {
                achievementTypeData = {
                  id: typeId,
                  title: includedType.attributes.title || 'Unknown Achievement',
                  description: includedType.attributes.description || '',
                  icon: includedType.attributes.icon || 'Trophy'
                };
              }
            }
            
            return {
              id: item.id,
              achieved_at: attrs.achieved_at || attrs.achievedAt || new Date().toISOString(),
              achievement_type: achievementTypeData
            };
          });
          
          console.log('Processed achievements:', achievementsData);
          setAchievements(achievementsData);
        } 
        // Handle array response format (fallback)
        else if (Array.isArray(data) && data.length > 0) {
          console.log('Processing achievements array format');
          setAchievements(data);
        } else {
          console.log('No achievements found or unexpected format:', data);
          setAchievements([]);
        }
        
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