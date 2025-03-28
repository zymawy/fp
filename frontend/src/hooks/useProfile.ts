import { useState, useEffect } from 'react';
import { useToast } from '@/components/ui/use-toast';
import { useAuth, dispatchAuthStateChangeEvent } from './useAuth';
import { api } from '@/lib/api';

export interface Profile {
  id: string;
  first_name: string | null;
  last_name: string | null;
  avatar_url: string | null;
  phone_number: string | null;
  email: string;
  updated_at: string;
}

interface ProfileResponse {
  data: {
    type: string;
    id: string;
    attributes: {
      name: string;
      email: string;
      first_name: string | null;
      last_name: string | null;
      avatar_url: string | null;
      phone_number: string | null;
      created_at: string;
      updated_at: string;
    }
  }
}

export function useProfile() {
  const [profile, setProfile] = useState<Profile | null>(null);
  const [loading, setLoading] = useState(true);
  const { user } = useAuth();
  const { toast } = useToast();

  const fetchProfile = async () => {
    if (!user) return;
    try {
      console.log('Fetching profile data for user:', user.id);
      const response = await api.get<ProfileResponse>('profile');
      console.log('Profile API response:', response);

      if (!response || !response.data) throw new Error('Profile not found or API returned empty response');

      // Transform the JsonAPI response to match our Profile interface
      const profileData: Profile = {
        id: response.data.id,
        first_name: response.data.attributes.first_name,
        last_name: response.data.attributes.last_name,
        avatar_url: response.data.attributes.avatar_url,
        phone_number: response.data.attributes.phone_number,
        email: response.data.attributes.email,
        updated_at: response.data.attributes.updated_at,
      };

      console.log('Processed profile data:', profileData);
      setProfile(profileData);
    } catch (error) {
      console.error('Error fetching profile:', error);
      
      // Fall back to using basic user data from auth if available
      if (user) {
        console.log('Falling back to basic user data from auth');
        const fallbackProfile: Profile = {
          id: user.id,
          first_name: user.firstName || user.name?.split(' ')[0] || '',
          last_name: user.lastName || user.name?.split(' ')[1] || '',
          avatar_url: user.avatar_url || null,
          phone_number: user.phoneNumber || null,
          email: user.email,
          updated_at: new Date().toISOString(),
        };
        setProfile(fallbackProfile);
      } else {
        toast({
          title: "Error fetching profile",
          description: error instanceof Error ? error.message : "An error occurred",
          variant: "destructive"
        });
      }
    } finally {
      setLoading(false);
    }
  };

  const updateProfile = async (updates: Partial<Profile>) => {
    try {
      if (!user) throw new Error("No user logged in");

      const data = await api.post<Profile>('profile', updates);

      if (!data) throw new Error('Failed to update profile');

      // Update the profile state
      const updatedProfile = (prev: Profile | null) => prev ? { ...prev, ...updates } : null;
      setProfile(updatedProfile);
      
      // Update localStorage session data
      try {
        const storedUser = localStorage.getItem('session');
        if (storedUser) {
          const userData = JSON.parse(storedUser);
          // Update the userData with new profile info
          const updatedUserData = {
            ...userData,
            // Update the corresponding fields in the user object
            firstName: updates.first_name || userData.firstName,
            lastName: updates.last_name || userData.lastName,
            avatar_url: updates.avatar_url || userData.avatar_url,
            phoneNumber: updates.phone_number || userData.phoneNumber,
          };
          
          // Save updated user data back to localStorage
          localStorage.setItem('session', JSON.stringify(updatedUserData));
          
          // Notify the app about the auth state change
          dispatchAuthStateChangeEvent();
          
          console.log('Updated user data in localStorage:', updatedUserData);
        }
      } catch (storageError) {
        console.error('Error updating localStorage:', storageError);
        // Continue even if localStorage update fails, as backend is already updated
      }
      
      toast({
        title: "Profile updated",
        description: "Your profile has been successfully updated",
      });

      return true;
    } catch (error) {
      toast({
        title: "Error updating profile",
        description: error instanceof Error ? error.message : "An error occurred",
        variant: "destructive"
      });
      return false;
    }
  };

  useEffect(() => {
    if (user) {
      fetchProfile();
    }
  }, [user]);

  return {
    profile,
    loading,
    updateProfile
  };
}