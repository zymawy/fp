import React from 'react';

const Navbar: React.FC = () => {
  return (
    <nav className="bg-white dark:bg-gray-800 shadow-md">
      <div className="container mx-auto px-6 py-4 flex justify-between items-center">
        <a href="/" className="text-2xl font-bold text-primary">YourLogo</a>
        <div className="space-x-6 text-sm font-medium">
          <a href="/" className="text-gray-700 dark:text-gray-300 hover:text-primary transition">Home</a>
          <a href="/causes" className="text-gray-700 dark:text-gray-300 hover:text-primary transition">Causes</a>
          <a href="/donate" className="text-gray-700 dark:text-gray-300 hover:text-primary transition">Donate</a>
          <a href="/profile" className="text-gray-700 dark:text-gray-300 hover:text-primary transition">Profile</a>
        </div>
      </div>
    </nav>
  );
};

export default Navbar;
