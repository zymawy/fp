import React, { useState } from 'react';
import CentrifugoTest from '../components/CentrifugoTest';
import { Layout } from '../components/Layout';

const CentrifugoTestPage: React.FC = () => {
  const [causeId, setCauseId] = useState<string>('test-123');
  const [inputCauseId, setInputCauseId] = useState<string>('test-123');

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    setCauseId(inputCauseId);
  };

  return (
    <Layout>
      <div className="container mx-auto p-4">
        <h1 className="text-2xl font-bold mb-6">Centrifugo Connection Test</h1>
        
        <div className="mb-6 p-4 bg-gray-50 rounded">
          <form onSubmit={handleSubmit} className="flex items-end gap-4">
            <div className="flex-1">
              <label htmlFor="causeId" className="block text-sm font-medium text-gray-700 mb-1">
                Cause ID
              </label>
              <input
                type="text"
                id="causeId"
                value={inputCauseId}
                onChange={(e) => setInputCauseId(e.target.value)}
                className="w-full p-2 border rounded"
                placeholder="Enter cause ID"
              />
            </div>
            <button
              type="submit"
              className="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"
            >
              Connect
            </button>
          </form>
        </div>
        
        <div className="mb-6 p-4 bg-blue-50 rounded">
          <p className="mb-2 text-sm">
            <strong>Instructions:</strong>
          </p>
          <ol className="list-decimal ml-5 text-sm space-y-1">
            <li>Enter a Cause ID above (or leave default)</li>
            <li>Connect to start receiving updates</li>
            <li>Open a terminal in the backend directory</li>
            <li>Make sure you're logged in to get a valid auth token (required for Centrifugo connections)</li>
            <li>Run one of these commands to test:
              <ul className="list-disc ml-5 mt-1 mb-1">
                <li><code className="bg-gray-100 px-1 py-0.5 rounded">php artisan centrifugo:publish cause.{causeId} --data='{JSON.stringify({"causeId":causeId,"raisedAmount":1000,"progressPercentage":75,"donorCount":10})}'</code></li>
                <li>Or use Laravel broadcast:
                  <pre className="bg-gray-100 mt-1 p-2 rounded text-xs overflow-x-auto">
{`// In a controller or command:
broadcast(new \\App\\Events\\DonationUpdated([
    'causeId' => '${causeId}',  
    'raisedAmount' => 1000,
    'progressPercentage' => 75,
    'donorCount' => 10
]));`}
                  </pre>
                </li>
              </ul>
            </li>
            <li>Watch as real-time updates appear below</li>
          </ol>
          
          <div className="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded">
            <h3 className="text-sm font-medium text-yellow-800 mb-1">Note about Centrifugo channel naming:</h3>
            <p className="text-xs text-yellow-700">
              The channel format is <code className="bg-gray-100 px-1 py-0.5 rounded">cause.{causeId}</code> and requires token authentication.
              Make sure your Laravel Centrifugo config is set up properly and the Laravel broadcaster is using the correct channel format.
            </p>
          </div>
        </div>
        
        <CentrifugoTest causeId={causeId} />
      </div>
    </Layout>
  );
};

export default CentrifugoTestPage; 