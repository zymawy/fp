import React, { useState, useEffect, useMemo, useRef } from 'react';
import { Progress } from '@/components/ui/progress';
import { cn } from '@/lib/utils';
import centrifugoService, { DonationUpdateData } from '@/services/CentrifugoService';
import { useTranslation } from 'react-i18next';

interface DonationProgressLiveProps {
  causeId: string;
  initialProgress: number;
  initialRaisedAmount: number | string;
  targetAmount: number | string;
  className?: string;
  showAmount?: boolean;
  currencySymbol?: string;
  showConnectionStatus?: boolean;
}

export function DonationProgressLive({
  causeId,
  initialProgress: initialProgressProp,
  initialRaisedAmount: initialRaisedAmountProp,
  targetAmount: targetAmountProp,
  className = '',
  showAmount = true,
  currencySymbol = '$',
  showConnectionStatus = false
}: DonationProgressLiveProps) {
  const { t } = useTranslation();
  
  // Safe conversions of initial values with validation
  const initialProgress = useMemo(() => {
    const parsedValue = typeof initialProgressProp === 'number' 
      ? initialProgressProp 
      : parseFloat(String(initialProgressProp));
    
    // If the parsed value is 0 or NaN, we want to show 0%
    if (parsedValue === 0 || isNaN(parsedValue)) {
      return 0;
    }
    
    return Math.min(Math.max(parsedValue, 0), 100); // Ensure between 0 and 100
  }, [initialProgressProp]);

  const initialRaisedAmount = useMemo(() => {
    const parsedValue = typeof initialRaisedAmountProp === 'number' 
      ? initialRaisedAmountProp 
      : parseFloat(String(initialRaisedAmountProp));
    
    return !isNaN(parsedValue) && parsedValue >= 0 ? parsedValue : 0;
  }, [initialRaisedAmountProp]);

  const targetAmount = useMemo(() => {
    const parsedValue = typeof targetAmountProp === 'number' 
      ? targetAmountProp 
      : parseFloat(String(targetAmountProp));
    
    return !isNaN(parsedValue) && parsedValue > 0 ? parsedValue : 1; // Prevent division by zero
  }, [targetAmountProp]);

  const [progress, setProgress] = useState(initialProgress);
  const [raisedAmount, setRaisedAmount] = useState(initialRaisedAmount);
  const [connectionStatus, setConnectionStatus] = useState<string>('');
  const [isUpdating, setIsUpdating] = useState(false);
  const hasSubscribed = useRef(false);

  // Check if we're in development mode
  const isDev = process.env.NODE_ENV === 'development';

  // Format currency safely
  const formatCurrency = (amount: number): string => {
    try {
      return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      }).format(amount);
    } catch (error) {
      console.error('Error formatting currency:', error);
      return amount.toFixed(2);
    }
  };

  useEffect(() => {
    if (hasSubscribed.current) return;
    
    try {
      // Verify that the centrifugoService exists
      if (!centrifugoService || typeof centrifugoService.subscribeToCause !== 'function') {
        if (isDev) {
          console.log('Centrifugo subscription not available');
        }
        setConnectionStatus('');
        return;
      }
      
      // Connect to Centrifugo
      centrifugoService.connect();
      
      // Set up status change subscription
      const statusUnsubscribe = centrifugoService.onStatusChange((status) => {
        // Only update connection status if we're showing it
        if (showConnectionStatus || isDev) {
          setConnectionStatus(status === 'connected' 
            ? 'Connected to live updates' 
            : status === 'disconnected' 
              ? 'Disconnected from live updates' 
              : status === 'connecting'
                ? 'Connecting to live updates...'
                : 'Live updates error');
        } else {
          setConnectionStatus('');
        }
      });
      
      // Subscribe to donation updates
      centrifugoService.subscribeToCause(
        causeId,
        (data: DonationUpdateData) => {
          // Handle donation updates
          setIsUpdating(true);
          
          // Update raised amount if provided
          if (typeof data.raisedAmount === 'number' && !isNaN(data.raisedAmount) && data.raisedAmount >= 0) {
            setRaisedAmount(data.raisedAmount);
          }
          
          // Update progress if provided
          if (typeof data.progressPercentage === 'number' && !isNaN(data.progressPercentage)) {
            setProgress(Math.min(Math.max(data.progressPercentage, 0), 100));
          } else if (targetAmount > 0) {
            // Recalculate progress based on raised amount
            const newProgress = Math.min(Math.round((raisedAmount / targetAmount) * 100), 100);
            setProgress(newProgress);
          }
          
          // Hide the update animation after a delay
          setTimeout(() => setIsUpdating(false), 2000);
        },
        (error) => {
          // Handle subscription errors
          if (isDev) {
            console.error('Error subscribing to donation updates:', error);
          }
          
          // Only show error status if explicitly requested
          if (showConnectionStatus) {
            setConnectionStatus('Error connecting to live updates');
          } else {
            setConnectionStatus('');
          }
        }
      );
      
      hasSubscribed.current = true;
      
      // Cleanup on unmount
      return () => {
        if (typeof centrifugoService.unsubscribeFromCause === 'function') {
          centrifugoService.unsubscribeFromCause(causeId);
        }
        
        if (typeof statusUnsubscribe === 'function') {
          statusUnsubscribe();
        }
      };
    } catch (error) {
      if (isDev) {
        console.error('Failed to subscribe to cause: ' + causeId, error);
      }
      
      // Only show error in development or if explicitly requested
      if (showConnectionStatus) {
        setConnectionStatus('Live updates unavailable');
      } else {
        setConnectionStatus('');
      }
    }
  }, [causeId, targetAmount, raisedAmount, isDev, showConnectionStatus]);

  return (
    <div className={`w-full ${className}`}>
      {showAmount && (
        <div className="flex justify-between items-center mb-2 text-sm">
          <span className={`transition-colors duration-300 ${isUpdating ? 'text-green-600 dark:text-green-400 font-bold' : ''}`}>
            {currencySymbol}{formatCurrency(raisedAmount)} {t('common.raised')}
          </span>
          <span className="text-muted-foreground">
            {t('common.goal')}: {currencySymbol}{formatCurrency(targetAmount)}
          </span>
        </div>
      )}
      
      <div className="relative">
        <Progress 
          value={progress === 0 ? 0.1 : progress}
          className={`h-2 ${isUpdating ? 'bg-green-100 dark:bg-green-950' : ''}`}
        />
        {progress === 0 && (
          <div className="absolute top-0 left-0 w-full h-full">
            <div className="text-[9px] text-muted-foreground pl-1">0%</div>
          </div>
        )}
      </div>
      
      {connectionStatus && (showConnectionStatus || isDev) && (
        <div className="text-xs text-muted-foreground mt-1 flex items-center">
          <div className={`w-2 h-2 rounded-full mr-1 ${
            connectionStatus.includes('Connected') 
              ? 'bg-green-500' 
              : connectionStatus.includes('not available') || connectionStatus.includes('unavailable')
                ? 'bg-gray-400' 
                : 'bg-amber-500'
          }`} />
          {connectionStatus}
        </div>
      )}
    </div>
  );
}
