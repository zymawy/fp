import React, { useState, useEffect } from 'react';
import { useCentrifugo } from '../contexts/CentrifugoContext';
import centrifugoService, { DonationUpdateData } from '../services/CentrifugoService';
import { Button } from './ui/button';
import { Alert, AlertDescription, AlertTitle } from './ui/alert';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from './ui/card';
import centrifugoClient from '../lib/centrifuge';

interface CentrifugoTestProps {
  causeId: string;
}

const CentrifugoTest: React.FC<CentrifugoTestProps> = ({ causeId }) => {
  const { isConnected, connectionStatus, reconnect, diagnosticInfo } = useCentrifugo();
  const [updates, setUpdates] = useState<DonationUpdateData[]>([]);
  const [subscribed, setSubscribed] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [diagnostics, setDiagnostics] = useState<any>(null);
  const [connectionCheck, setConnectionCheck] = useState<any>(null);
  const [isChecking, setIsChecking] = useState(false);
  const [clientErrors, setClientErrors] = useState<Error[]>([]);
  
  useEffect(() => {
    try {
      // Get any existing client errors
      const errors = centrifugoClient.getErrors();
      if (errors && errors.length > 0) {
        setClientErrors(errors);
        setError(`Client errors detected: ${errors.length}`);
      }
    
      centrifugoService.subscribeToCause(
        causeId,
        (data: DonationUpdateData) => {
          setUpdates(prev => [data, ...prev].slice(0, 10));
          setError(null);
          setSubscribed(true);
        },
        (err: Error) => {
          console.error('Error subscribing to updates:', err);
          setError(`Subscription error: ${err.message}`);
          setSubscribed(false);
        }
      );
      
      // Cleanup on unmount
      return () => {
        centrifugoService.unsubscribeFromCause(causeId);
      };
    } catch (err) {
      const errorMessage = err instanceof Error ? err.message : String(err);
      setError(`Failed to subscribe: ${errorMessage}`);
      return () => {};
    }
  }, [causeId]);
  
  const handleGetDiagnostics = () => {
    const info = diagnosticInfo();
    
    // Enhance diagnostics with network information and client errors
    const enhancedDiagnostics = {
      ...info,
      browser: {
        userAgent: navigator.userAgent,
        language: navigator.language,
        online: navigator.onLine
      },
      network: {
        type: (navigator as any).connection ? (navigator as any).connection.effectiveType : 'unknown',
        rtt: (navigator as any).connection ? (navigator as any).connection.rtt : 'unknown',
        downlink: (navigator as any).connection ? (navigator as any).connection.downlink : 'unknown'
      },
      clientErrors: clientErrors.map(e => e.message)
    };
    
    setDiagnostics(enhancedDiagnostics);
  };
  
  const handleCheckConnection = async () => {
    setIsChecking(true);
    try {
      const result = await centrifugoService.checkConnection();
      setConnectionCheck(result);
    } catch (err) {
      console.error('Error checking connection:', err);
      setConnectionCheck({
        error: String(err),
        status: 'error'
      });
    } finally {
      setIsChecking(false);
    }
  };
  
  return (
    <div className="space-y-4">
      <Card>
        <CardHeader>
          <CardTitle>Centrifugo Connection Status</CardTitle>
          <CardDescription>Testing real-time updates for cause: {causeId}</CardDescription>
        </CardHeader>
        <CardContent>
          <div className="grid gap-2">
            <div className="flex items-center gap-2">
              <div className={`w-3 h-3 rounded-full ${
                isConnected ? 'bg-green-500' : 
                connectionStatus === 'error' ? 'bg-red-500' : 'bg-amber-500'
              }`} />
              <span className="font-medium">
                {isConnected ? 'Connected' : 
                 connectionStatus === 'error' ? 'Connection Error' : 'Disconnected'}
              </span>
            </div>
            
            <div className="text-sm text-muted-foreground">
              <div>Status: {connectionStatus}</div>
              <div>Subscribed to updates: {subscribed ? 'Yes' : 'No'}</div>
            </div>
          </div>
          
          {error && (
            <Alert variant="destructive" className="mt-4">
              <AlertTitle>Connection Error</AlertTitle>
              <AlertDescription>{error}</AlertDescription>
            </Alert>
          )}
          
          {clientErrors.length > 0 && (
            <Alert variant="destructive" className="mt-4">
              <AlertTitle>Client Errors ({clientErrors.length})</AlertTitle>
              <AlertDescription className="max-h-32 overflow-auto">
                <ul className="list-disc pl-4 space-y-1">
                  {clientErrors.map((err, idx) => (
                    <li key={idx} className="text-xs">{err.message}</li>
                  ))}
                </ul>
              </AlertDescription>
            </Alert>
          )}
          
          {diagnostics && (
            <div className="mt-4">
              <h3 className="font-medium text-sm mb-2">Diagnostic Information</h3>
              <div className="p-3 bg-gray-50 dark:bg-gray-800 rounded overflow-auto text-xs">
                <pre>{JSON.stringify(diagnostics, null, 2)}</pre>
              </div>
              <div className="mt-2 grid gap-2">
                <div className="text-xs text-muted-foreground">
                  <strong>Status:</strong> {diagnostics.status}
                </div>
                {diagnostics.config && (
                  <>
                    <div className="text-xs text-muted-foreground">
                      <strong>WebSocket URL:</strong> {diagnostics.config.url}
                    </div>
                    <div className="text-xs text-muted-foreground">
                      <strong>Token Authentication:</strong> {diagnostics.config.usingTokenAuth ? 'Yes' : 'No'}
                    </div>
                    <div className="text-xs text-muted-foreground">
                      <strong>Page Protocol:</strong> {diagnostics.config.pageProtocol}
                    </div>
                  </>
                )}
                <div className="text-xs text-muted-foreground">
                  <strong>Browser Online:</strong> {diagnostics.browser?.online ? 'Yes' : 'No'}
                </div>
              </div>
            </div>
          )}
        </CardContent>
        <CardFooter className="flex gap-2">
          <Button onClick={reconnect} variant="outline">Reconnect</Button>
          <Button onClick={handleGetDiagnostics} variant="outline">Diagnostics</Button>
          <Button 
            onClick={handleCheckConnection} 
            variant="outline"
            disabled={isChecking}
          >
            {isChecking ? 'Checking...' : 'Check Connection'}
          </Button>
        </CardFooter>
      </Card>
      
      {connectionCheck && (
        <Card>
          <CardHeader>
            <CardTitle>Connection Check Results</CardTitle>
            <CardDescription>Diagnostic test results for connectivity</CardDescription>
          </CardHeader>
          <CardContent>
            <div className="space-y-3">
              <div className="grid grid-cols-2 gap-2 text-sm">
                <div>API Server:</div>
                <div className={`font-medium ${connectionCheck.apiAvailable ? 'text-green-600' : 'text-red-600'}`}>
                  {connectionCheck.apiAvailable ? 'Available' : 'Not Available'}
                </div>
                
                <div>Token Endpoint:</div>
                <div className={`font-medium ${connectionCheck.tokenEndpointAvailable ? 'text-green-600' : 'text-red-600'}`}>
                  {connectionCheck.tokenEndpointAvailable ? 'Available' : 'Not Available'}
                </div>
                
                <div>Centrifugo Server:</div>
                <div className={`font-medium ${connectionCheck.centrifugoAvailable ? 'text-green-600' : 'text-red-600'}`}>
                  {connectionCheck.centrifugoAvailable ? 'Available' : 'Not Available'}
                </div>
              </div>
              
              {connectionCheck.errors && connectionCheck.errors.length > 0 && (
                <div className="mt-3">
                  <h4 className="text-sm font-medium mb-1">Errors:</h4>
                  <ul className="text-xs text-red-600 space-y-1 pl-5 list-disc">
                    {connectionCheck.errors.map((err: string, i: number) => (
                      <li key={i}>{err}</li>
                    ))}
                  </ul>
                </div>
              )}
            </div>
          </CardContent>
        </Card>
      )}
      
      {updates.length > 0 && (
        <Card>
          <CardHeader>
            <CardTitle>Recent Updates</CardTitle>
            <CardDescription>Last {updates.length} updates received</CardDescription>
          </CardHeader>
          <CardContent>
            <div className="space-y-2">
              {updates.map((update, index) => (
                <div key={index} className="p-3 bg-gray-50 dark:bg-gray-800 rounded">
                  <div className="grid grid-cols-2 gap-2 text-sm">
                    <div>Raised Amount:</div>
                    <div className="font-medium">${update.raisedAmount.toFixed(2)}</div>
                    
                    <div>Progress:</div>
                    <div className="font-medium">{update.progressPercentage}%</div>
                    
                    {update.donorCount !== undefined && (
                      <>
                        <div>Donors:</div>
                        <div className="font-medium">{update.donorCount}</div>
                      </>
                    )}
                  </div>
                </div>
              ))}
            </div>
          </CardContent>
        </Card>
      )}
    </div>
  );
};

export default CentrifugoTest; 