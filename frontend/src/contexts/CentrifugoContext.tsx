import React, { createContext, useContext, useState, useEffect, ReactNode } from 'react';
import centrifugoService from '../services/CentrifugoService';

interface CentrifugoContextType {
  isConnected: boolean;
  connectionStatus: string;
  reconnect: () => void;
  diagnosticInfo: () => any;
}

const CentrifugoContext = createContext<CentrifugoContextType | undefined>(undefined);

interface CentrifugoProviderProps {
  children: ReactNode;
}

export const CentrifugoProvider: React.FC<CentrifugoProviderProps> = ({ children }) => {
  const [connectionStatus, setConnectionStatus] = useState<string>('disconnected');
  
  useEffect(() => {
    // Initialize connection when provider mounts
    centrifugoService.connect();
    
    // Subscribe to connection status changes
    const unsubscribe = centrifugoService.onStatusChange((status) => {
      setConnectionStatus(status);
    });
    
    // Cleanup on unmount
    return () => {
      unsubscribe();
    };
  }, []);
  
  const reconnect = () => {
    centrifugoService.disconnect();
    setTimeout(() => centrifugoService.connect(), 500);
  };
  
  const diagnosticInfo = () => {
    return centrifugoService.getDiagnosticInfo();
  };
  
  return (
    <CentrifugoContext.Provider
      value={{
        isConnected: connectionStatus === 'connected',
        connectionStatus,
        reconnect,
        diagnosticInfo,
      }}
    >
      {children}
    </CentrifugoContext.Provider>
  );
};

export const useCentrifugo = (): CentrifugoContextType => {
  const context = useContext(CentrifugoContext);
  
  if (context === undefined) {
    throw new Error('useCentrifugo must be used within a CentrifugoProvider');
  }
  
  return context;
}; 