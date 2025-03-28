import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { useToast } from '@/components/ui/use-toast';
import { api } from '@/lib/api';

interface User {
  id: string;
  email: string;
  name?: string;
  firstName?: string | null;
  lastName?: string | null;
  phoneNumber?: string | null;
  avatar_url?: string | null;
  createdAt?: Date;
  updatedAt?: Date;
  password?: string;
}

interface AuthResponse {
  success: boolean;
  data: {
    user: User;
    access_token: string;
    token_type: string;
    expires_in: number;
  };
}

// Create a custom event for auth state changes
export const AUTH_STATE_CHANGE_EVENT = 'auth_state_change';

// Function to dispatch auth state change event
export const dispatchAuthStateChangeEvent = () => {
  const event = new CustomEvent(AUTH_STATE_CHANGE_EVENT);
  window.dispatchEvent(event);
};

export function useAuth() {
  const [user, setUser] = useState<User | null>(null);
  const [loading, setLoading] = useState(true);
  const navigate = useNavigate();
  const { toast } = useToast();
  const [token, setToken] = useState<string | null>(null);

  // Function to load user data from localStorage
  const loadUserFromStorage = () => {
    const storedUser = localStorage.getItem('session');
    const storedToken = localStorage.getItem('token');
    
    if (storedUser) {
      try {
        const userData = JSON.parse(storedUser);
        console.log('Found user in localStorage:', userData);
        setUser(userData);
      } catch (error) {
        console.error('Error parsing stored user data:', error);
        localStorage.removeItem('session');
      }
    } else {
      console.log('No user found in localStorage, user needs to sign in');
      setUser(null);
    }
    
    if (storedToken) {
      console.log('Found auth token in localStorage');
      setToken(storedToken);
    } else {
      console.log('No auth token found in localStorage');
      setToken(null);
    }
    
    setLoading(false);
  };

  // Load user data on initial mount
  useEffect(() => {
    loadUserFromStorage();
    
    // Listen for auth state change events
    const handleAuthStateChange = () => {
      loadUserFromStorage();
    };
    
    window.addEventListener(AUTH_STATE_CHANGE_EVENT, handleAuthStateChange);
    
    return () => {
      window.removeEventListener(AUTH_STATE_CHANGE_EVENT, handleAuthStateChange);
    };
  }, []);

  const signUp = async (email: string, password: string, firstName: string = '', lastName: string = '') => {
    try {
      const name = `${firstName} ${lastName}`.trim();
      
      // Use api helper for sign up
      const userData = await api.auth.signUp({ email, password, name });
      
      // Toast notification
      toast({
        title: "Success!",
        description: "Account created successfully.",
      });

      navigate('/signin');
      return userData;
    } catch (error) {
      console.error('Signup error:', error);
      toast({
        title: "Registration failed",
        description: error instanceof Error ? error.message : "An error occurred during signup",
        variant: "destructive"
      });
      throw error;
    }
  };

  const signIn = async (email: string, password: string) => {
    try {
      // Use API helper for sign in
      const response = await api.auth.signIn({ email, password }) as AuthResponse;
      
      if (!response.success || !response.data) {
        throw new Error('Invalid credentials');
      }
      
      const { user: userData, access_token: authToken } = response.data;
      
      setUser(userData);
      setToken(authToken);
      
      // Store in localStorage for persistence
      localStorage.setItem('session', JSON.stringify(userData));
      localStorage.setItem('token', authToken);
      
      console.log('User signed in successfully:', userData);

      toast({
        title: "Welcome back!",
        description: "You have successfully signed in.",
      });

      // Dispatch auth state change event
      dispatchAuthStateChangeEvent();
      
      navigate('/');
      return userData;
    } catch (error) {
      console.error('Sign in error:', error);
      toast({
        variant: "destructive",
        title: "Error",
        description: error instanceof Error ? error.message : "Invalid credentials",
      });
      throw error;
    }
  };

  const signOut = async () => {
    try {
      // Use the API helper for sign out
      await api.auth.signOut();
      
      // Clear state and localStorage
      setUser(null);
      setToken(null);
      localStorage.removeItem('session');
      localStorage.removeItem('token');
      
      console.log('User signed out successfully');

      toast({
        title: "Signed out",
        description: "You have been successfully signed out.",
      });

      // Dispatch auth state change event
      dispatchAuthStateChangeEvent();

      navigate('/');
    } catch (error) {
      console.error('Sign out error:', error);
      toast({
        variant: "destructive",
        title: "Error",
        description: error instanceof Error ? error.message : "An error occurred during sign out",
      });
    }
  };

  const resetPassword = async (email: string) => {
    try {
      await api.auth.forgotPassword(email);

      toast({
        title: "Check your email",
        description: "We've sent you a password reset link.",
      });
    } catch (error) {
      console.error('Reset password error:', error);
      toast({
        variant: "destructive",
        title: "Error",
        description: error instanceof Error ? error.message : "An error occurred",
      });
    }
  };

  // For development debugging purposes
  if (import.meta.env.DEV && !user) {
    console.warn('No authenticated user. You need to sign in to access authenticated features.');
  }

  return {
    user,
    loading,
    signUp,
    signIn,
    signOut,
    resetPassword,
  };
}