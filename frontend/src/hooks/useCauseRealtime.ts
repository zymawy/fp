import { useState, useEffect } from 'react';
import centrifugoService, { DonationUpdateData } from '../services/CentrifugoService';
import { useCentrifugo } from '../contexts/CentrifugoContext';

/**
 * Hook to subscribe to real-time updates for a specific cause
 */
export function useCauseRealtime(causeId: string) {
  const { isConnected } = useCentrifugo();
  const [latestUpdate, setLatestUpdate] = useState<DonationUpdateData | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [isSubscribed, setIsSubscribed] = useState(false);

  useEffect(() => {
    // Subscribe to donation updates for this cause
    try {
      centrifugoService.subscribeToCause(
        causeId,
        (data: DonationUpdateData) => {
          setLatestUpdate(data);
          setError(null);
          setIsSubscribed(true);
        },
        (err: Error) => {
          setError(`Error connecting to real-time updates: ${err.message}`);
          setIsSubscribed(false);
        }
      );
      
      // Cleanup on unmount
      return () => {
        centrifugoService.unsubscribeFromCause(causeId);
      };
    } catch (err) {
      setError(`Failed to initialize real-time connection: ${err}`);
      setIsSubscribed(false);
      return () => {};
    }
  }, [causeId]);

  return {
    isConnected,
    isSubscribed,
    latestUpdate,
    error
  };
} 