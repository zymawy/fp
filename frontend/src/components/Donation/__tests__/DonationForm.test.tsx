import React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { vi } from 'vitest';
import DonationForm from '../DonationForm';
import { api } from '../../../lib/api';

// Mock the API client
vi.mock('../../../lib/api', () => ({
  api: {
    post: vi.fn().mockImplementation(() => Promise.resolve({ success: true })),
  }
}));

describe('DonationForm', () => {
  const mockCause = {
    id: '123',
    title: 'Test Cause',
    description: 'Test description',
    raisedAmount: 5000,
    goalAmount: 10000
  };

  beforeEach(() => {
    vi.clearAllMocks();
  });

  test('renders donation form correctly', () => {
    render(<DonationForm cause={mockCause} onSuccess={() => {}} />);
    
    // Check if important elements are rendered
    expect(screen.getByText(/Donation Amount/i)).toBeInTheDocument();
    expect(screen.getByLabelText(/Amount/i)).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /Donate/i })).toBeInTheDocument();
  });

  test('validates amount field', async () => {
    render(<DonationForm cause={mockCause} onSuccess={() => {}} />);
    
    // Try to submit without entering amount
    fireEvent.click(screen.getByRole('button', { name: /Donate/i }));
    
    // Check for validation error
    await waitFor(() => {
      expect(screen.getByText(/Please enter a donation amount/i)).toBeInTheDocument();
    });
  });

  test('validates minimum amount', async () => {
    render(<DonationForm cause={mockCause} onSuccess={() => {}} />);
    
    // Enter a small amount
    fireEvent.change(screen.getByLabelText(/Amount/i), { target: { value: '0.50' } });
    fireEvent.click(screen.getByRole('button', { name: /Donate/i }));
    
    // Check for validation error
    await waitFor(() => {
      expect(screen.getByText(/Minimum donation amount is \$1/i)).toBeInTheDocument();
    });
  });

  test('submits form with valid data', async () => {
    const mockSuccess = vi.fn();
    render(<DonationForm cause={mockCause} onSuccess={mockSuccess} />);
    
    // Fill in form data
    fireEvent.change(screen.getByLabelText(/Amount/i), { target: { value: '50' } });
    
    // Submit form
    fireEvent.click(screen.getByRole('button', { name: /Donate/i }));
    
    // Wait for submission
    await waitFor(() => {
      expect(api.post).toHaveBeenCalledWith('/donations', {
        causeId: mockCause.id,
        donationAmount: 50,
      });
      expect(mockSuccess).toHaveBeenCalled();
    });
  });

  test('displays error message when donation fails', async () => {
    // Mock API to reject
    (api.post as any).mockRejectedValueOnce(new Error('Donation failed'));
    
    render(<DonationForm cause={mockCause} onSuccess={() => {}} />);
    
    // Fill in form data
    fireEvent.change(screen.getByLabelText(/Amount/i), { target: { value: '50' } });
    
    // Submit form
    fireEvent.click(screen.getByRole('button', { name: /Donate/i }));
    
    // Wait for error
    await waitFor(() => {
      expect(screen.getByText(/An error occurred while processing your donation/i)).toBeInTheDocument();
    });
  });
}); 