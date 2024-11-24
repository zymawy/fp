import React from 'react';

const CauseDetails: React.FC = () => {
  const cause = {
    title: 'Feed the Hungry',
    image: '/images/cause1.jpg',
    description: 'Detailed information about the cause...',
    progress: 75,
    targetAmount: 10000,
    raisedAmount: 7500,
  };

  return (
    <section className="py-12 bg-white">
      <div className="container mx-auto px-6 lg:px-8">
        <div className="flex flex-col lg:flex-row">
          <img src={cause.image} alt={cause.title} className="w-full lg:w-1/2 rounded-lg mb-6 lg:mb-0 lg:mr-8" />
          <div className="flex-1">
            <h1 className="text-4xl font-bold mb-4">{cause.title}</h1>
            <p className="text-lg text-gray-700 mb-6">{cause.description}</p>
            <div className="relative pt-1">
              <div className="overflow-hidden h-2 mb-4 text-xs flex rounded bg-emerald-200">
                <div className="flex flex-col text-center whitespace-nowrap text-white justify-center bg-emerald-600" style={{ width: `${cause.progress}%` }}></div>
              </div>
              <p className="text-gray-600 text-sm">Raised: ${cause.raisedAmount} of ${cause.targetAmount}</p>
            </div>
            <a href="/donate" className="bg-indigo-600 text-white py-3 px-6 rounded-lg font-semibold hover:bg-indigo-700 transition">Donate Now</a>
          </div>
        </div>
      </div>
    </section>
  );
};

export default CauseDetails;
