import { Centrifuge } from 'centrifuge';
import { api } from './api';

// Define response interfaces just for documentation
interface CentrifugoTokensResponse {
  connection_token: string;
  subscription_tokens: Record<string, string>;
}

interface CauseSubscriptionResponse {
  subscription_token: string;
  channel: string;
}

class CentrifugeClient {
  private static instance: CentrifugeClient;
  private client: Centrifuge | null = null;
  private subscriptions: Map<string, { sub: any; count: number }>;
  private isInitialized = false;
  private connectionPromise: Promise<void> | null = null;
  private initializeErrors: Error[] = [];

  private constructor() {
    this.subscriptions = new Map();
  }

  public static getInstance(): CentrifugeClient {
    if (!CentrifugeClient.instance) {
      CentrifugeClient.instance = new CentrifugeClient();
    }
    return CentrifugeClient.instance;
  }

  // Get the last initialization error
  public getErrors(): Error[] {
    return this.initializeErrors;
  }

  async initialize() {
    if (this.isInitialized) return;
    
    if (this.connectionPromise) {
      return this.connectionPromise;
    }
    
    this.connectionPromise = new Promise<void>(async (resolve, reject) => {
      try {
        // Get connection token from backend
        const response = await api.get('/centrifugo/tokens');
        
        // Handle the response data which might be nested or not
        const responseData: any = response.data;
        const connectionToken = responseData.connection_token || 
                               (responseData.data && responseData.data.connection_token);
                               
        if (!connectionToken) {
          throw new Error('Invalid token response format from server');
        }
        
        // Create Centrifugo client with token
        const wsProtocol = window.location.protocol === 'https:' ? 'wss' : 'ws';
        const centrifugoHost = import.meta.env.VITE_CENTRIFUGO_HOST || 'localhost';
        const centrifugoPort = import.meta.env.VITE_CENTRIFUGO_PORT || '8000';
        const wsEndpoint = `${wsProtocol}://${centrifugoHost}:${centrifugoPort}/connection/websocket`;
        
        console.log('Connecting to Centrifugo at:', wsEndpoint);
        
        this.client = new Centrifuge(wsEndpoint, {
          token: connectionToken,
        });
        
        this.client.on('connecting', (ctx) => {
          console.log('Connecting to Centrifugo...', ctx);
        });
        
        this.client.on('connected', (ctx) => {
          console.log('Connected to Centrifugo', ctx);
          this.isInitialized = true;
          resolve();
        });
        
        this.client.on('disconnected', (ctx) => {
          console.log('Disconnected from Centrifugo', ctx);
          this.isInitialized = false;
        });
        
        this.client.on('error', (ctx) => {
          const error = new Error(`Centrifugo error: ${JSON.stringify(ctx)}`);
          console.error(error);
          this.initializeErrors.push(error);
          
          if (!this.isInitialized) {
            reject(error);
          }
        });
        
        // Connect to Centrifugo
        this.client.connect();
      } catch (error) {
        console.error('Failed to initialize Centrifugo:', error);
        this.connectionPromise = null;
        this.initializeErrors.push(error instanceof Error ? error : new Error(String(error)));
        reject(error);
      }
    });
    
    return this.connectionPromise;
  }

  async subscribeToCause(causeId: string, callback: (ctx: any) => void) {
    try {
      await this.initialize();
      if (!this.client) {
        throw new Error('Centrifugo client not initialized');
      }
      
      const channel = `cause.${causeId}`;
      
      let subscription = this.subscriptions.get(channel);
      if (subscription) {
        subscription.count++;
        subscription.sub.on('publication', callback);
        return () => this.unsubscribe(channel, callback);
      }

      // Custom token fetching function for the subscription
      const getToken = async (ctx: any) => {
        try {
          const response = await api.get(`/centrifugo/tokens/cause/${causeId}`);
          
          // Handle the response data which might be nested or not
          const responseData: any = response.data;
          const subscriptionToken = responseData.subscription_token || 
                                   (responseData.data && responseData.data.subscription_token);
          
          if (!subscriptionToken) {
            throw new Error('Invalid subscription token response');
          }
          
          return subscriptionToken;
        } catch (error) {
          console.error(`Failed to get subscription token for ${causeId}:`, error);
          throw error;
        }
      };
      
      // Create a new subscription with getToken function
      const sub = this.client.newSubscription(channel, {
        getToken: getToken
      });
      
      sub.on('publication', callback);
      
      sub.on('subscribed', (ctx) => {
        console.log(`Subscribed to ${channel}`, ctx);
      });
      
      sub.on('error', (ctx) => {
        console.error(`Subscription error for ${channel}:`, ctx);
      });
      
      sub.subscribe();
      
      this.subscriptions.set(channel, { sub, count: 1 });
      return () => this.unsubscribe(channel, callback);
    } catch (error) {
      console.error(`Failed to subscribe to cause ${causeId}:`, error);
      return () => {};
    }
  }

  private unsubscribe(channel: string, callback: (ctx: any) => void) {
    const subscription = this.subscriptions.get(channel);
    if (!subscription) return;

    subscription.sub.removeListener('publication', callback);
    subscription.count--;

    if (subscription.count <= 0) {
      subscription.sub.unsubscribe();
      this.subscriptions.delete(channel);
    }
  }

  disconnect() {
    this.subscriptions.forEach(({ sub }) => sub.unsubscribe());
    this.subscriptions.clear();
    if (this.client) {
      this.client.disconnect();
      this.client = null;
    }
    this.isInitialized = false;
    this.connectionPromise = null;
  }
}

const centrifugoClient = CentrifugeClient.getInstance();
export default centrifugoClient;