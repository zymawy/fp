import React from 'react';

const Footer: React.FC = () => {
  return (
    <footer className="bg-gray-800 text-gray-400 py-8">
      <div className="container mx-auto text-center">
        <p className="text-sm">&copy; 2024 Your Non-Profit Organization. All Rights Reserved.</p>
        <div className="mt-4 space-x-6">
          <a href="/" className="hover:text-white transition">Privacy Policy</a>
          <a href="/" className="hover:text-white transition">Terms of Service</a>
          <a href="/" className="hover:text-white transition">Contact Us</a>
        </div>
      </div>
    </footer>
  );
};

export default Footer;
