import { Centrifuge } from 'centrifuge';

class CentrifugeClient {
  private static instance: CentrifugeClient;
  private client: Centrifuge;
  private subscriptions: Map<string, { sub: any; count: number }>;

  private constructor() {
    this.client = new Centrifuge('wss://your-centrifugo-server:8000/connection/websocket', {
      token: 'your-jwt-token',
    });
    this.subscriptions = new Map();
    this.client.connect();
  }

  public static getInstance(): CentrifugeClient {
    if (!CentrifugeClient.instance) {
      CentrifugeClient.instance = new CentrifugeClient();
    }
    return CentrifugeClient.instance;
  }

  subscribe(channel: string, callback: (ctx: any) => void) {
    let subscription = this.subscriptions.get(channel);

    if (!subscription) {
      const sub = this.client.newSubscription(channel);
      sub.on('publication', callback);
      sub.subscribe();
      
      this.subscriptions.set(channel, { sub, count: 1 });
      return () => this.unsubscribe(channel, callback);
    }

    subscription.count++;
    subscription.sub.on('publication', callback);
    return () => this.unsubscribe(channel, callback);
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
    this.client.disconnect();
  }
}

export default CentrifugeClient.getInstance();