import React from 'react';

const HeroSection: React.FC = () => {
  return (
    <div className="bg-gradient-to-r from-primary to-secondary text-white py-20">
      <div className="container mx-auto text-center">
        <h1 className="text-5xl font-extrabold mb-6">Empower Change with Your Contribution</h1>
        <p className="text-lg mb-10 max-w-2xl mx-auto">
          Join us in making a meaningful impact. Your donations help bring positive change to those who need it the most.
        </p>
        <a
          href="/donate"
          className="inline-block bg-white text-primary py-3 px-8 rounded-full font-semibold shadow-md hover:bg-gray-100 transition duration-300"
        >
          Donate Now
        </a>
      </div>
    </div>
  );
};

export default HeroSection;
