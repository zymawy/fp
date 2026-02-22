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
      throw new Error('Cannot create donation - missing required fields');
    }
  } catch (error) {
    throw error;
  }
}

export async function getPaymentMethods(amount?: number) {
  try {
    // Use provided amount or default to 100
    const invoiceAmount = amount && !isNaN(amount) ? amount : 100;
    const currency = getCurrencyByLanguage();
    
    // Try to fetch payment methods from our Laravel backend
    try {
      // Construct URL with query parameters
      const url = `/payment-methods?amount=${invoiceAmount}&currency=${currency}`;
      
      const laravelResponse = await fetchApi<any>(url, {
        method: 'GET'
      });
      
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

        return methods;
      }
    } catch {
      // Fall through to use fallback payment methods
    }

    // Return a reasonable fallback set of payment methods
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
  } catch {
    return [
      {
        PaymentMethodId: 2,
        PaymentMethodEn: 'Credit Card',
        PaymentMethodAr: 'بطاقة ائتمان',
        ImageUrl: 'https://api.myfatoorah.com/Files/Images/visa.png',
        IsDirectPayment: true,
        stringId: '2'
      }
    ];
  }
}

export async function verifyPayment(paymentId: string) {
  try {
    const laravelResponse = await fetchApi<any>(`/payments/${paymentId}/status`, {
      method: 'GET',
    });

    if (laravelResponse && !laravelResponse.error) {
      return laravelResponse.data || laravelResponse;
    } else {
      throw new Error('Payment verification failed');
    }
  } catch {
    throw new Error('Payment verification service is unavailable');
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
    // Step 1: Verify the payment using the Laravel backend
    const verificationResult = await verifyPayment(paymentId);

    // Check if verification failed
    if (!verificationResult || verificationResult.error) {
      return {
        success: false,
        error: verificationResult?.error || 'Payment verification failed',
      };
    }

    // Step 2: Ensure payment status is 'paid'
    const paymentStatus =
      verificationResult.data?.InvoiceStatus?.toLowerCase() ||
      verificationResult.data?.status?.toLowerCase() ||
      verificationResult.status?.toLowerCase() ||
      '';

    const isPaid = ['paid', 'success', 'successful', 'completed'].includes(paymentStatus);

    if (!isPaid) {
      return {
        success: false,
        error: `Payment not completed. Current status: ${paymentStatus}`,
      };
    }

    // Step 3: Try to find donation by payment ID
    try {
      const existingDonation = await fetchApi<any>(`/donations/by-payment/${paymentId}`);

      if (existingDonation && !existingDonation.error) {
        const donation = existingDonation.data || existingDonation;

        // If donation status is not completed, update it
        if (donation.payment_status !== 'completed') {
          await fetchApi<any>(`/donations/${donation.id}`, {
            method: 'PUT',
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
            },
            body: { payment_status: 'completed' },
          });
        }

        return {
          success: true,
          donationId: donation.id,
          data: donation,
        };
      }
    } catch {
      // No existing donation found, will create new below
    }

    // Step 4: Update existing donation if donation ID was passed
    if (donationData.donationId) {
      try {
        const updateResponse = await fetchApi<any>(`/donations/${donationData.donationId}`, {
          method: 'PUT',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
          },
          body: {
            payment_status: 'completed',
            payment_id: paymentId,
          },
        });

        if (!updateResponse.error) {
          const donationResult = updateResponse.data || updateResponse;
          return {
            success: true,
            donationId: donationResult.id,
            data: donationResult,
          };
        }
      } catch {
        // Fall through to create a new donation
      }
    }

    // Step 5: Create a new donation record
    if (!donationData.causeId) {
      return { success: false, error: 'Cause ID is required' };
    }

    try {
      const response = await fetchApi<any>('/donations', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: {
          ...donationData,
          payment_id: paymentId,
          payment_status: 'completed',
        },
      });

      if (response.error) {
        throw new Error(response.error || 'Failed to create donation');
      }

      const donationResult = response.data || response;
      return {
        success: true,
        donationId: donationResult.id,
        data: donationResult,
      };
    } catch (error) {
      return {
        success: false,
        error: error instanceof Error ? error.message : 'Failed to create donation',
      };
    }
  } catch (error) {
    return {
      success: false,
      error: error instanceof Error ? error.message : 'Unexpected error during payment verification',
    };
  }
}