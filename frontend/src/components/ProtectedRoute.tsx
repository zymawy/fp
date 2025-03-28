import { useEffect } from 'react';
import { Navigate, useLocation } from 'react-router-dom';
import { useAuth } from '@/hooks/useAuth';

interface ProtectedRouteProps {
  children: React.ReactNode;
}

export function ProtectedRoute({ children }: ProtectedRouteProps) {
  const { user, loading } = useAuth();
  const location = useLocation();

  // While checking auth state, show nothing
  if (loading) {
    return null;
  }

  // If user is not authenticated, redirect to sign in with return path
  if (!user) {
    return <Navigate to="/signin" state={{ from: location }} replace />;
  }

  // User is authenticated, render the protected content
  return <>{children}</>;
} 