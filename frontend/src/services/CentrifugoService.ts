import centrifugoClient from '../lib/centrifuge';

// Define message data structure to match Laravel's DonationUpdated event
export interface DonationUpdateData {
  causeId: string;  // Updated to match event property
  raisedAmount: number;  // Updated to match event property
  progressPercentage: number;  // Updated to match event property
  donorCount?: number;  // Added for donor count updates
}

// Types for subscription callbacks
export type MessageCallback = (data: DonationUpdateData) => void;
export type StatusCallback = (status: string) => void;
export type ErrorCallback = (error: Error) => void;

class CentrifugoService {
  private subscriptions: Map<string, () => void> = new Map();
  private connectionStatus: string = 'disconnected';
  private statusCallbacks: StatusCallback[] = [];
  private autoReconnect: boolean = true;

  constructor() {
    // Setup event listeners for the Centrifugo client
    this.setupConnectionMonitoring();
  }

  private async setupConnectionMonitoring(): Promise<void> {
    try {
      // Initialize the client which will connect automatically
      await centrifugoClient.initialize();
      this.connectionStatus = 'connected';
      this.notifyStatusChange('connected');
    } catch (error) {
      console.error('Error initializing Centrifugo client:', error);
      this.connectionStatus = 'error';
      this.notifyStatusChange('error');
      
      if (this.autoReconnect) {
        console.log('Will attempt to reconnect in 3 seconds...');
        setTimeout(() => this.connect(), 3000);
      }
    }
  }
  
  public async connect(): Promise<void> {
    if (this.connectionStatus !== 'connected') {
      try {
        console.log('Attempting to connect to Centrifugo...');
        this.connectionStatus = 'connecting';
        this.notifyStatusChange('connecting');
        
        await centrifugoClient.initialize();
        
        this.connectionStatus = 'connected';
        this.notifyStatusChange('connected');
      } catch (err) {
        console.error('Failed to connect to Centrifugo:', err);
        this.connectionStatus = 'error';
        this.notifyStatusChange('error');
        
        if (this.autoReconnect) {
          console.log('Will attempt to reconnect in 3 seconds...');
          setTimeout(() => this.connect(), 3000);
        }
      }
    }
  }
  
  public disconnect(): void {
    if (this.connectionStatus === 'connected') {
      centrifugoClient.disconnect();
      this.connectionStatus = 'disconnected';
      this.notifyStatusChange('disconnected');
    }
  }
  
  public async subscribeToCause(causeId: string, onMessage: MessageCallback, onError?: ErrorCallback): Promise<void> {
    const channel = `cause.${causeId}`;
    
    if (this.subscriptions.has(channel)) {
      console.log(`Already subscribed to ${channel}`);
      return;
    }
    
    try {
      const unsubscribe = await centrifugoClient.subscribeToCause(causeId, (message: any) => {
        // Handle message format
        console.log(`Received update for cause ${causeId}:`, message);
        
        let eventData: DonationUpdateData;
        const publication = message.data || {};
        
        // Try to extract the data from various possible formats
        if (publication.data) {
          // Standard Centrifugo format
          eventData = publication.data;
        } else {
          // Fallback - try to adapt whatever format we got
          eventData = {
            causeId: publication.causeId || publication.cause_id || causeId,
            raisedAmount: publication.raisedAmount || publication.raised_amount || 0,
            progressPercentage: publication.progressPercentage || publication.progress_percentage || 0,
            donorCount: publication.donorCount || publication.donor_count || publication.donors_count || publication.unique_donors
          };
        }
        
        console.log(`Processed update for cause ${causeId}:`, eventData);
        onMessage(eventData);
      });
      
      this.subscriptions.set(channel, unsubscribe);
      console.log(`Subscribed to ${channel}`);
    } catch (err) {
      console.error(`Failed to subscribe to ${channel}:`, err);
      this.connectionStatus = 'error'; 
      this.notifyStatusChange('error');
      if (onError) onError(err as Error);
    }
  }
  
  public unsubscribeFromCause(causeId: string): void {
    const channel = `cause.${causeId}`;
    
    if (this.subscriptions.has(channel)) {
      const unsubscribe = this.subscriptions.get(channel);
      if (unsubscribe && typeof unsubscribe === 'function') {
        unsubscribe();
      }
      this.subscriptions.delete(channel);
      console.log(`Unsubscribed from ${channel}`);
    }
  }
  
  public onStatusChange(callback: StatusCallback): () => void {
    this.statusCallbacks.push(callback);
    
    // Immediately call with current status
    callback(this.connectionStatus);
    
    // Return function to remove the callback
    return () => {
      this.statusCallbacks = this.statusCallbacks.filter(cb => cb !== callback);
    };
  }
  
  private notifyStatusChange(status: string): void {
    this.statusCallbacks.forEach(callback => callback(status));
  }
  
  public getConnectionStatus(): string {
    return this.connectionStatus;
  }
  
  public getDiagnosticInfo(): { status: string; config: Record<string, string | boolean>; } {
    const centrifugoHost = import.meta.env.VITE_CENTRIFUGO_HOST || 'localhost';
    const centrifugoPort = import.meta.env.VITE_CENTRIFUGO_PORT || '8000';
    
    let url = `${window.location.protocol === 'https:' ? 'wss' : 'ws'}://${centrifugoHost}:${centrifugoPort}/connection/websocket`;
    
    return {
      status: this.connectionStatus,
      config: {
        host: centrifugoHost,
        port: centrifugoPort,
        url,
        autoReconnect: this.autoReconnect,
        pageProtocol: window.location.protocol,
        pageHost: window.location.host,
        usingTokenAuth: true
      }
    };
  }

  public async checkConnection(): Promise<Record<string, any>> {
    const result: Record<string, any> = {
      status: this.connectionStatus,
      apiAvailable: false,
      tokenEndpointAvailable: false,
      centrifugoAvailable: false,
      errors: []
    };
    
    // Check network connectivity
    if (!navigator.onLine) {
      result.errors.push('Browser is offline');
      return result;
    }
    
    // Check API availability
    try {
      // Try a simple HEAD request to the API to check availability
      const apiResponse = await fetch(import.meta.env.VITE_API_SERVER || '', { 
        method: 'HEAD',
        cache: 'no-store'
      });
      result.apiAvailable = apiResponse.ok;
      result.apiStatus = apiResponse.status;
    } catch (error) {
      result.errors.push(`API not reachable: ${error}`);
    }
    
    // Check Centrifugo token endpoint
    try {
      const tokenResponse = await fetch(`${import.meta.env.VITE_API_SERVER}/api/centrifugo/tokens`, {
        method: 'HEAD',
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token') || ''}`,
          'Content-Type': 'application/json'
        },
        cache: 'no-store'
      });
      result.tokenEndpointAvailable = tokenResponse.ok;
      result.tokenEndpointStatus = tokenResponse.status;
    } catch (error) {
      result.errors.push(`Token endpoint not reachable: ${error}`);
    }
    
    // Check Centrifugo server
    try {
      const centrifugoHost = import.meta.env.VITE_CENTRIFUGO_HOST || 'localhost';
      const centrifugoPort = import.meta.env.VITE_CENTRIFUGO_PORT || '8000';
      const infoUrl = `http://${centrifugoHost}:${centrifugoPort}/info`;
      
      try {
        const centrifugoResponse = await fetch(infoUrl, { 
          method: 'HEAD',
          cache: 'no-store'
        });
        result.centrifugoAvailable = centrifugoResponse.ok;
        result.centrifugoStatus = centrifugoResponse.status;
      } catch (error) {
        result.errors.push(`Centrifugo server not reachable: ${error}`);
      }
    } catch (error) {
      result.errors.push(`Error checking Centrifugo: ${error}`);
    }
    
    return result;
  }
}

// Create a singleton instance
const centrifugoService = new CentrifugoService();

export default centrifugoService; 