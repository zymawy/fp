import path from 'path';
import react from '@vitejs/plugin-react';
import { defineConfig } from 'vite';
import dotenv from 'dotenv';
// Load environment variables
dotenv.config();

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
