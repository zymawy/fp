'use client'

import React from 'react';

const CauseListing: React.FC = () => {
  const causes = [
    { id: 1, title: 'Feed the Hungry', image: '/images/cause1.jpg', description: 'Help provide meals...', progress: 75 },
    { id: 2, title: 'Support Education', image: '/images/cause2.jpg', description: 'Fund school supplies...', progress: 60 },
    // Add more causes
  ];

  return (
    <section className="py-12 bg-gray-50">
      <div className="container mx-auto px-6 lg:px-8">
        <h2 className="text-3xl font-bold text-center mb-8">Our Causes</h2>
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
          {causes.map(cause => (
            <CauseCard key={cause.id} cause={cause} />
          ))}
        </div>
      </div>
    </section>
  );
};

const CauseCard: React.FC<{ cause: { id: number; title: string; image: string; description: string; progress: number } }> = ({ cause }) => (
  <div className="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300">
    <img src={cause.image} alt={cause.title} className="w-full h-48 object-cover" />
    <div className="p-4">
      <h3 className="text-xl font-semibold mb-2">{cause.title}</h3>
      <p className="text-sm text-gray-600 mb-4">{cause.description}</p>
      <div className="relative pt-1">
        <div className="overflow-hidden h-2 mb-4 text-xs flex rounded bg-emerald-200">
          <div className="flex flex-col text-center whitespace-nowrap text-white justify-center bg-emerald-600" style={{ width: `${cause.progress}%` }}></div>
        </div>
        <p className="text-gray-600 text-sm">Progress: {cause.progress}%</p>
      </div>
      <a href={`/causes/${cause.id}`} className="text-indigo-600 font-semibold hover:underline">Donate Now</a>
    </div>
  </div>
);

export default CauseListing;
