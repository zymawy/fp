// Payment integration library
import i18next from 'i18next';
import { fetchApi } from "@/lib/api";

// Get currency based on language
export const getCurrencyByLanguage = (): string => {
  // Use USD for English, SAR for Arabic
  return i18next.language === 'ar' ? 'SAR' : 'USD';
};

// Get currency display symbol based on currency code
export const getCurrencySymbol = (currencyCode: string): string => {
  const symbols: Record<string, string> = {
    'USD': '$',
    'SAR': 'ر.س',
    'AED': 'د.إ',
    'KWD': 'د.ك',
    'BHD': 'د.ب',
    'QAR': 'ر.ق',
    'OMR': 'ر.ع',
  };
  
  return symbols[currencyCode] || currencyCode;
};

export interface PaymentRequest {
  amount: number;
  customerName: string;
  customerEmail: string;
  customerPhone?: string;
  paymentMethod: string;
  callbackUrl: string;
  errorUrl: string;
  currencyIso?: string; // Optional, will use language-based default if not provided
  isGift?: boolean;
  recipientName?: string;
  recipientEmail?: string;
  giftMessage?: string;
  isAnonymous?: boolean;
  coverFees?: boolean;
  processingFee?: number;
  causeId: string; // Required to link payment to cause
  userId: string; // Required to link payment to user
}

// Define an interface for payment methods
export interface PaymentMethod {
  PaymentMethodId: number;
  PaymentMethodEn: string;
  PaymentMethodAr?: string;
  ImageUrl?: string;
  IsDirectPayment: boolean;
  ServiceCharge?: number;
  TotalAmount?: number;
  CurrencyIso?: string;
  [key: string]: any; // For any other properties
}

// Define an interface for Laravel payment methods
export interface LaravelPaymentMethod {
  id?: number;
  payment_method_id?: number;
  name?: string;
  name_en?: string;
  name_ar?: string;
  image_url?: string;
  logo?: string;
  is_direct?: boolean;
  [key: string]: any; // For any other properties that Laravel might return
}

// Main function to execute a payment and create pending donation
export async function executePayment({
  amount,
  customerName,
  customerEmail,
  customerPhone,
  paymentMethod,
  callbackUrl,
  errorUrl,
  currencyIso,
  isGift,
  recipientName,
  recipientEmail,
  giftMessage,
  isAnonymous,
  coverFees,
  processingFee,
  causeId,
  userId
}: PaymentRequest) {
  try {
    // Step 1: Execute the payment first to get the real payment ID
    // Use provided currency or get default based on language
    const currency = currencyIso || getCurrencyByLanguage();
    
    // Ensure payment method is properly parsed as number
    const paymentMethodId = parseInt(paymentMethod, 10);
    
    if (isNaN(paymentMethodId)) {
      throw new Error('Invalid payment method selected');
    }
    
    // Create invoice items
    const invoiceItems = [
      {
        ItemName: isGift ? `Gift Donation for ${recipientName || 'Recipient'}` : 'Donation',
        Quantity: 1,
        UnitPrice: amount
      }
    ];
    
    // Call the payments/execute endpoint on the Laravel backend to get payment URL and ID
    const paymentData = {
      paymentMethodId: paymentMethodId,
      invoiceValue: amount,
      currencyIso: currency,
      customerName: customerName,
      customerEmail: customerEmail,
      customerPhone: customerPhone,
      callBackUrl: callbackUrl,
      errorUrl: errorUrl,
      language: i18next.language || 'en',
      displayCurrencyIso: currency,
      customerReference: causeId, // Use cause ID as customer reference
      invoiceItems: invoiceItems,
      cause_id: causeId,
        user_id: userId || '',
        amount: amount || 0,
        total_amount: amount || 0,
        is_anonymous: isAnonymous || false,
        is_gift: isGift || false,
        recipient_name: recipientName || null,
        recipient_email: recipientEmail || null,
        gift_message: giftMessage || null,
        cover_fees: coverFees || false,
        processing_fee: processingFee || 0,
        payment_method_id: paymentMethod,
        currency_code: currencyIso || 'USD',
        payment_status: 'pending',
    };

    console.log('Executing payment with data:', paymentData);
    const responseData = await fetchApi<any>('/payments/process', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify(paymentData)
    });
    
    const response = responseData.data;
    // Check if response is valid
    if (!response || response.error) {
      throw new Error(response?.error?.message || 'Payment execution failed');
    }
    
    // Extract the real payment ID from the response
    const invoiceId = response.invoice_id || response.paymentId;
    
    console.log('Creating donation with real invoice ID:', invoiceId);
      console.log('response.data', response.data);
      
    // Step 2: Now create a donation record with the real payment ID
    if (causeId && (amount || 0) > 0 && invoiceId) {
      
      // Format donation data according to Laravel API expectations
      // const donationData = {
      //   cause_id: causeId,
      //   user_id: userId || '',
      //   amount: amount || 0,
      //   total_amount: amount || 0,
      //   is_anonymous: isAnonymous || false,
      //   is_gift: isGift || false,
      //   recipient_name: recipientName || null,
      //   recipient_email: recipientEmail || null,
      //   gift_message: giftMessage || null,
      //   cover_fees: coverFees || false,
      //   processing_fee: processingFee || 0,
      //   payment_method_id: paymentMethod,
      //   currency_code: currencyIso || 'USD',
      //   payment_status: 'pending', // Initial status is pending
      //   payment_id: invoiceId // Use the real invoice ID from the payment
      // };
      
      // // Use properly formatted headers for the donation request
      // const donationResponse = await fetchApi<any>('/donations', {
      //   method: 'POST',
      //   headers: {
      //     'Content-Type': 'application/json',
      //     'Accept': 'application/json'
      //   },
      //   body: JSON.stringify(donationData)
      // });
      
      // console.log('Donation creation response:', donationResponse);
      
      // if (donationResponse && !donationResponse.error) {
      //   // Handle Laravel API response format which might include data wrapper
      //   const donationId = donationResponse.data?.id || donationResponse.id;
      //   console.log('Created donation with ID:', donationId);
        
        // Update the callback URL with donation ID
        // if (response.data && response.PaymentURL && response.PaymentURL.includes('?')) {
        //   response.PaymentURL += `&donationId=${donationId}`;
        // } else if (donationId && response.PaymentURL) {
        
          window.location.href = response.payment_url += `&donationId=${response.donation_id}`;

      // } else {
      //   // Log any error from donation creation
      //   console.error('Failed to create donation:', donationResponse?.error || 'Unknown error');
      // }
    } else {
      console.error('Cannot create donation - missing required fields', { causeId, amount, invoiceId });
    }
    
    // Return the payment data for redirection
    // return response.data || response;
  } catch (error) {
    console.error('Payment execution error:', error);
    throw error;
  }
}

export async function getPaymentMethods(amount?: number) {
  try {
    // Use provided amount or default to 100
    const invoiceAmount = amount && !isNaN(amount) ? amount : 100;
    const currency = getCurrencyByLanguage();
    
    // Log the request that's about to be made
    console.log(`Requesting payment methods for amount ${invoiceAmount} ${currency}`, {
      invoiceAmount,
      currency
    });
    
    // Try to fetch payment methods from our Laravel backend
    try {
      // Construct URL with query parameters
      const url = `/payment-methods?amount=${invoiceAmount}&currency=${currency}`;
      
      const laravelResponse = await fetchApi<any>(url, {
        method: 'GET'
      });
      
      console.log('Payment methods from Laravel backend:', laravelResponse);
      
      // Check for valid response with payment methods
      if (laravelResponse && laravelResponse.success && 
          laravelResponse.data && laravelResponse.data.payment_methods && 
          Array.isArray(laravelResponse.data.payment_methods) && 
          laravelResponse.data.payment_methods.length > 0) {
        
        // Direct access to payment_methods array
        const methods = laravelResponse.data.payment_methods.map((method: any) => ({
          PaymentMethodId: method.PaymentMethodId || method.payment_method_id || method.id,
          PaymentMethodEn: method.PaymentMethodEn || method.name_en || method.name,
          PaymentMethodAr: method.PaymentMethodAr || method.name_ar,
          ImageUrl: method.ImageUrl || method.image_url || method.logo,
          IsDirectPayment: method.IsDirectPayment || method.is_direct || true,
          ServiceCharge: method.ServiceCharge || method.service_charge || 0,
          TotalAmount: method.TotalAmount || method.total_amount || invoiceAmount,
          CurrencyIso: method.CurrencyIso || method.currency || currency,
          stringId: String(method.PaymentMethodId || method.payment_method_id || method.id)
        }));
        
        console.log('Mapped payment methods from Laravel:', methods);
        return methods;
      }
      
      // Check for older response formats
      if (laravelResponse && Array.isArray(laravelResponse.data) && laravelResponse.data.length > 0) {
        // Handle array directly in data property
        const methods = laravelResponse.data.map((method: any) => ({
          PaymentMethodId: method.payment_method_id || method.id,
          PaymentMethodEn: method.name_en || method.name,
          PaymentMethodAr: method.name_ar,
          ImageUrl: method.image_url || method.logo,
          IsDirectPayment: method.is_direct || true,
          ServiceCharge: method.service_charge || 0,
          TotalAmount: method.total_amount || invoiceAmount,
          CurrencyIso: method.currency || currency,
          stringId: String(method.payment_method_id || method.id)
        }));
        
        console.log('Mapped payment methods from Laravel (legacy format):', methods);
        return methods;
      }
    } catch (laravelError) {
      console.warn('Failed to get payment methods from Laravel:', laravelError);
    }
    
    // Return a reasonable fallback set of payment methods instead of calling MyFatoorah directly
    console.warn('Using fallback payment methods as direct MyFatoorah calls are disabled');
    const fallbackMethods = [
      {
        PaymentMethodId: 2,
        PaymentMethodEn: 'Credit Card',
        PaymentMethodAr: 'بطاقة ائتمان',
        ImageUrl: 'https://api.myfatoorah.com/Files/Images/visa.png',
        IsDirectPayment: true,
        stringId: '2'
      }
    ];
    
    return fallbackMethods;
  } catch (error) {
    console.error('Failed to fetch payment methods:', error);
    
    // Return a reasonable fallback set of payment methods to prevent UI from breaking
    const fallbackMethods = [
      {
        PaymentMethodId: 2,
        PaymentMethodEn: 'Credit Card',
        PaymentMethodAr: 'بطاقة ائتمان',
        ImageUrl: 'https://api.myfatoorah.com/Files/Images/visa.png',
        IsDirectPayment: true,
        stringId: '2'
      }
    ];
    
    console.warn('Using fallback payment methods:', fallbackMethods);
    return fallbackMethods;
  }
}

export async function verifyPayment(paymentId: string) {
  try {
    console.log('Verifying payment with ID:', paymentId);
    
    // Only verify payment through Laravel backend
    try {
      const laravelResponse = await fetchApi<any>(`/payments/${paymentId}/status`, {
        method: 'GET'
      });
      
      console.log('Payment verification from Laravel backend:', laravelResponse);
      
      if (laravelResponse && !laravelResponse.error) {
        // Return the verified payment data from Laravel
        return laravelResponse.data || laravelResponse;
      } else {
        console.error('Laravel payment verification failed:', laravelResponse);
        throw new Error('Payment verification failed');
      }
    } catch (laravelError) {
      console.error('Failed to verify payment through Laravel:', laravelError);
      throw new Error('Payment verification service is unavailable');
    }
  } catch (error) {
    console.error('Payment verification failed:', error);
    throw error;
  }
}

/**
 * Ensures payment is verified and marked as paid before creating a donation
 * @param paymentId The payment ID
 * @param donationData The donation data to be saved
 * @returns Object containing success status and any error message
 */
export async function verifyAndCreateDonation(paymentId: string, donationData: any): Promise<any> {
  try {
    console.log('Verifying payment status for ID:', paymentId);
    
    // Step 1: Verify the payment using the Laravel backend
    const verificationResult = await verifyPayment(paymentId);
    console.log('Verification result:', verificationResult);
    
    // Check if verification failed
    if (!verificationResult || verificationResult.error) {
      console.error('Payment verification failed:', verificationResult?.error || 'Unknown error');
      return { 
        success: false, 
        error: verificationResult?.error || 'Payment verification failed'
      };
    }
    
    // Step 2: Ensure payment status is 'paid'
    // Various status formats, so check for variations
    const paymentStatus = verificationResult.data?.InvoiceStatus?.toLowerCase() || 
                          verificationResult.data?.status?.toLowerCase() || 
                          verificationResult.status?.toLowerCase() || '';
                          
    console.log('Payment status:', paymentStatus);
    
    // Check for approved payment status - could be 'paid', 'success', 'successful' or similar
    const isPaid = ['paid', 'success', 'successful', 'completed'].includes(paymentStatus);
    
    if (!isPaid) {
      console.error('Payment status not paid:', paymentStatus);
      return { 
        success: false, 
        error: `Payment not completed. Current status: ${paymentStatus}`
      };
    }
    
    // Step 3: Try to find donation by payment ID
    try {
      const existingDonation = await fetchApi<any>(`/donations/by-payment/${paymentId}`);
      
      // If donation already exists with this payment ID, update its status
      if (existingDonation && !existingDonation.error) {
        const donation = existingDonation.data || existingDonation;
        console.log('Found existing donation with payment ID:', donation);
        
        // If donation status is not completed, update it
        if (donation.payment_status !== 'completed') {
          const updateResponse = await fetchApi<any>(`/donations/${donation.id}`, {
            method: 'PUT',
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json'
            },
            body: JSON.stringify({
              payment_status: 'completed'
            })
          });
          
          if (updateResponse.error) {
            console.error('Failed to update donation status:', updateResponse.error);
          } else {
            console.log('Successfully updated donation status to completed');
          }
        }
        
        return {
          success: true,
          donationId: donation.id,
          data: donation
        };
      }
    } catch (error) {
      console.log('No existing donation found with payment ID, will create new');
    }
    
    // Step 4: Check if we have a donation ID passed
    if (donationData.donationId) {
      console.log('Found donation ID:', donationData.donationId);
      try {
        // Update existing donation with payment ID and status
        const updateResponse = await fetchApi<any>(`/donations/${donationData.donationId}`, {
          method: 'PUT',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
          },
          body: JSON.stringify({
            payment_status: 'completed',
            payment_id: paymentId
          })
        });
        
        if (updateResponse.error) {
          console.error('Failed to update donation:', updateResponse.error);
          // Fall through to create a new one as backup
        } else {
          const donationResult = updateResponse.data || updateResponse;
          console.log('Successfully updated donation:', donationResult);
          
          return {
            success: true,
            donationId: donationResult.id,
            data: donationResult
          };
        }
      } catch (updateError) {
        console.error('Error updating donation:', updateError);
        // Fall through to create a new one as backup
      }
    }
    
    // Step 5: Create a new donation record if update failed or no donation ID
    console.log('Creating new donation with data:', donationData);
    try {
      // Ensure we have the required data
      if (!donationData.causeId) {
        return { success: false, error: 'Cause ID is required' };
      }
      
      const response = await fetchApi<any>('/donations', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify({
          ...donationData,
          payment_id: paymentId,
          payment_status: 'completed'
        })
      });
      
      if (response.error) {
        throw new Error(response.error || 'Failed to create donation');
      }
      
      const donationResult = response.data || response;
      console.log('Donation created successfully:', donationResult);
      
      return {
        success: true,
        donationId: donationResult.id,
        data: donationResult
      };
    } catch (error) {
      console.error('Error creating donation:', error);
      return { 
        success: false, 
        error: error instanceof Error ? error.message : 'Failed to create donation'
      };
    }
  } catch (error) {
    console.error('Error in verify and create donation:', error);
    return { 
      success: false, 
      error: error instanceof Error ? error.message : 'Unexpected error during payment verification'
    };
  }
}