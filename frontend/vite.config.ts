import path from 'path';
import react from '@vitejs/plugin-react';
import { defineConfig } from 'vite';
import dotenv from 'dotenv';
// Load environment variables
dotenv.config();

// Simple in-memory data store (for development only)
interface User {
  id: string;
  email: string;
  password: string;
  firstName?: string | null;
  lastName?: string | null;
  avatarUrl?: string | null;
  phoneNumber?: string | null;
  createdAt: string;
  updatedAt: string;
}

interface Category {
  id: string;
  name: string;
  slug: string;
  createdAt: string;
}

interface Cause {
  id: string;
  title: string;
  description: string;
  longDescription?: string;
  imageUrl: string;
  raisedAmount: number;
  goalAmount: number;
  donorCount: number;
  categoryId: string;
  status: string;
  urgencyLevel: string;
  location?: string;
  startDate: string;
  endDate?: string;
  createdAt: string;
  updatedAt: string;
  featured: boolean;
  sliderButtonText?: string;
  sliderSubtitle?: string;
}

interface Donation {
  id: string;
  userId?: string;
  causeId: string;
  amount: number;
  isAnonymous: boolean;
  createdAt: string;
  totalAmount: number;
  coverFees: boolean;
  currencyCode: string;
  giftMessage?: string;
  isGift: boolean;
  paymentMethodId?: string;
  paymentStatus: string;
  processingFee?: number;
  recipientEmail?: string;
  recipientName?: string;
  paymentId?: string;
}

interface Partner {
  id: string;
  name: string;
  logo: string;
  website?: string;
  order: number;
  isActive: boolean;
  createdAt: string;
  updatedAt: string;
}

const db: {
  users: User[];
  causes: Cause[];
  categories: Category[];
  donations: Donation[];
  partners: Partner[];
} = {
  users: [],
  causes: [],
  categories: [],
  donations: [],
  partners: []
};

export default defineConfig({
  plugins: [react()],
  server: {
    // Proxy disabled as we're using direct ngrok URL
    // proxy: {
    //   '/api': {
    //     target: 'http://localhost:8001',
    //     changeOrigin: true,
    //     secure: false,
    //   }
    // }
  },
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src'),
    },
  }
});