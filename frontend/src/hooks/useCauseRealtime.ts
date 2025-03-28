import { useState, useEffect, useCallback } from 'react';
import centrifugoClient from '@/lib/centrifuge';

interface DonationUpdate {
  cause_id: string;
  title: string;
  raised_amount: number;
  target_amount: number;
  progress_percentage: number;
  timestamp: string;
}

/**
 * Hook to subscribe to real-time updates for a specific cause
 * @param causeId The ID of the cause to subscribe to
 */
export function useCauseRealtime(causeId: string) {
  const [isConnected, setIsConnected] = useState(false);
  const [latestUpdate, setLatestUpdate] = useState<DonationUpdate | null>(null);
  const [error, setError] = useState<string | null>(null);

  // Subscribe to real-time updates for the cause
  useEffect(() => {
    if (!causeId) {
      setError('Cause ID is required');
      return;
    }

    setError(null);
    let cleanupFunction: (() => void) | undefined;

    async function subscribeToUpdates() {
      try {
        // Clear previous connection status and updates
        setIsConnected(false);
        setLatestUpdate(null);

        // Set up the real-time subscription
        const unsubscribe = await centrifugoClient.subscribeToCause(
          causeId,
          (message) => {
            console.log('Received cause update:', message);
            
            // Update the latest donation data
            if (message && message.data) {
              setLatestUpdate(message.data);
              setIsConnected(true);
            }
          }
        );

        // Store the cleanup function to be called when unmounting
        cleanupFunction = unsubscribe;
      } catch (err) {
        console.error('Error subscribing to cause updates:', err);
        setError('Failed to connect to real-time updates. Please refresh the page.');
      }
    }

    // Initiate the subscription
    subscribeToUpdates();

    // Return cleanup function
    return () => {
      if (cleanupFunction) {
        cleanupFunction();
        setIsConnected(false);
      }
    };
  }, [causeId]);

  // Clean up and disconnect when component unmounts
  useEffect(() => {
    return () => {
      // No need to disconnect the client since other components might be using it
      // Just ensure we clean up our subscription
    };
  }, []);

  return {
    isConnected,
    latestUpdate,
    error,
  };
} 