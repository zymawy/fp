import React from 'react';
import MainLayout from './layouts/MainLayout';
import HeroSection from './components/HeroSection';

const HomePage: React.FC = () => {
  return (
    <MainLayout>
      <HeroSection />
      <div className="container mx-auto my-8">
        {/* Additional content goes here */}
        <p className="text-center text-gray-600">Welcome to our non-profit platform.</p>
      </div>
    </MainLayout>
  );
};

export default HomePage;
