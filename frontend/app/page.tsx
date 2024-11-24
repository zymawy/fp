'use client'

import React from 'react';
import HeroSection from './components/HeroSection';
import "@/lib/ziggy"
import { useRoute } from 'ziggy-js'
import { Ziggy } from '@/lib/ziggy';

useRoute(Ziggy);

const HomePage: React.FC = () => {
  return (
    <>
          <HeroSection />
      <div className="container mx-auto my-8">
        {/* Additional content goes here */}
        <p className="text-center text-gray-600">Welcome to our non-profit platform.</p>
      </div>
    </>
  );
};

export default HomePage;
