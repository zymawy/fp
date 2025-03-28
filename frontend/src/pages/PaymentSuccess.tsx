import React, { useEffect, useState } from 'react';
import { useSearchParams, useNavigate } from 'react-router-dom';
import { verifyPayment } from '@/lib/payment';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { CheckCircle2, ArrowLeft, LogIn, Download } from 'lucide-react';
import { Layout } from '@/components/Layout';
import { useTranslation } from 'react-i18next';
import { useAuth } from '@/hooks/useAuth';
import { useToast } from '@/components/ui/use-toast';
import { fetchApi } from '@/lib/api';

// Define interface for payment verification response in JSON:API format
interface PaymentVerificationResponse {
  data: {
    type: string;
    id: string;
    attributes: {
      transaction_id: string;
      payment_provider: string | null;
      payment_method: string;
      amount: number;
      currency_code: string;
      status: string | null;
      created_at: string;
      updated_at: string;
    }
  }
}

// Create a translation function for certificate generation
let tFunction: (key: string) => string;

// Function to generate a certificate
async function generateCertificate(data: {
  donationId: string;
  causeId: string;
  amount: number;
  currencyCode: string;
  userName: string;
  date: string;
  isGift: boolean;
}) {
  // Use the stored translation function
  const t = tFunction || ((key: string) => key);
  
  try {
    const response = await fetch('/api/certificates/generate', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        donationId: data.donationId,
        // Include other fields for certificate generation
        causeId: data.causeId,
        amount: data.amount,
        currencyCode: data.currencyCode,
        recipientName: data.userName,
        isGift: data.isGift,
        donationDate: data.date
      }),
    });

    if (!response.ok) {
      throw new Error(t('payment.certificateGenerationFailed'));
    }

    const result = await response.json();
    return result.data;
  } catch (error) {
    console.error('Error generating certificate:', error);
    throw error;
  }
}

// Function to send email
async function sendEmail(to: string, subject: string, body: string) {
  // Use the stored translation function
  const t = tFunction || ((key: string) => key);
  
  try {
    const response = await fetch('/api/emails/send', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        to,
        subject,
        body,
      }),
    });

    if (!response.ok) {
      throw new Error(t('payment.emailSendFailed'));
    }

    const result = await response.json();
    return result.data;
  } catch (error) {
    console.error('Error sending email:', error);
    throw error;
  }
}

export default function PaymentSuccess() {
  const { t, i18n } = useTranslation();
  // Store the translation function for use in other functions
  tFunction = t;
  const isRtl = i18n.language === 'ar';
  const [searchParams] = useSearchParams();
  const navigate = useNavigate();
  const { toast } = useToast();
  const { user } = useAuth();
  
  // State variables
  const [isVerifying, setIsVerifying] = useState(true);
  const [isSuccess, setIsSuccess] = useState(false);
  const [donation, setDonation] = useState<any>(null);
  const [error, setError] = useState<string | null>(null);
  const [certificate, setCertificate] = useState<any>(null);
  
  // Extract query parameters
  const paymentId = searchParams.get('paymentId') || '';
  const donationId = searchParams.get('donationId') || '';
  const transactionId = searchParams.get('transactionId') || '';
  
  // Process donation verification and completion
  useEffect(() => {
    const verify = async () => {
      setIsVerifying(true);
      
      try {
        // Step 1: Verify payment was successful
        if (!paymentId && !transactionId) {
          throw new Error(t('payment.missingPaymentInfo'));
        }
        
        const paymentVerification = await verifyPayment(paymentId || transactionId);
        console.log('Payment verification response:', paymentVerification);
        const { attributes } = paymentVerification;
        
          // Handle JSON:API format
          
          // Check payment method and amount to determine success
          if (attributes.payment_method && attributes.amount > 0) {
            // Consider it successful if it has a payment method and positive amount
            console.log('Payment verified via JSON:API format');
          } else {
            throw new Error(t('payment.verificationFailed'));
          }
          
                      
          const invoiceStatus =  attributes.status;
          if (!(invoiceStatus === 'Paid' || invoiceStatus === 'paid' || invoiceStatus === 'completed')) {
            throw new Error(t('payment.notComplete'));
          }
            // Set the donation data and trigger success processing
            setDonation(attributes.donation);
            await processDonationSuccess(attributes.donation);
            setIsSuccess(true);
        
      } catch (err) {
        console.error('Payment verification error:', err);
        setError(err instanceof Error ? err.message : t('payment.unexpectedError'));
        setIsSuccess(false);
      } finally {
        setIsVerifying(false);
      }
    };
    
    if (paymentId || donationId || transactionId) {
      verify();
    } else {
      setIsVerifying(false);
      setError(t('payment.missingUrlInfo'));
    }
  }, [paymentId, donationId, transactionId]);
  
  // Process a successful donation by generating certificate
  const processDonationSuccess = async (donationData: any) => {
    try {
      console.log('Processing successful donation for certificate:', donationData);
      
      // Check if the data is in JSON:API format with nested attributes
      const attributes = donationData.attributes || donationData;
      
    
      // Adapt to both camelCase and snake_case field names for compatibility
      const paymentStatus = attributes.payment_status || attributes.paymentStatus;
      const isGift = attributes.is_gift || attributes.isGift;
      const recipientName = attributes.user.name || attributes.recipient_name || attributes.recipientName;
      const causeId = attributes.cause_id || attributes.causeId;
      const amount = attributes.amount;
      const currencyCode = attributes.currency_code || attributes.currencyCode || 'USD';
      const createdAt = attributes.created_at || attributes.createdAt;
      const donationId = donationData.id || (donationData.data?.id) || attributes.id;
      
      // If we have donation data and it was paid, generate a certificate
      if (attributes && (paymentStatus === 'completed')) {
        const certificateResponse = await generateCertificate({
          donationId: donationId,
          causeId: causeId,
          amount: amount,
          currencyCode: currencyCode,
          userName: isGift ? 
            (recipientName || t('payment.anonymousDonor')) : 
            (user ? `${user.firstName || ''} ${user.lastName || ''}`.trim() : (recipientName || t('payment.anonymousDonor'))),
          date: new Date(createdAt).toISOString(),
          isGift: isGift
        });
        
        if (certificateResponse) {
          setCertificate(certificateResponse);
        }
      }
    } catch (error) {
      console.error('Certificate generation error:', error);
      toast({
        title: t('payment.certificateError'),
        description: t('payment.certificateErrorDescription'),
        variant: "destructive"
      });
    }
  };
  
  // Handle returning to home
  const handleReturnHome = () => {
    navigate('/');
  };
  
  // Handle downloading certificate
  const handleDownloadCertificate = () => {
    if (certificate?.certificateUrl) {
      window.open(certificate.certificateUrl, '_blank');
    }
  };
  
  // Handle going to login page
  const handleGoToLogin = () => {
    navigate('/login');
  };

  return (
    <Layout>
      <div className="container max-w-3xl py-8">
        <Card className="w-full">
          <CardHeader>
            <CardTitle className="text-center">
              {isVerifying 
                ? t('payment.verifying') 
                : (isSuccess ? t('payment.success') : t('payment.failed'))}
            </CardTitle>
            <CardDescription className="text-center">
              {isVerifying 
                ? t('payment.verifyingDescription') 
                : (isSuccess 
                  ? t('payment.successDescription') 
                  : t('payment.failedDescription'))}
            </CardDescription>
          </CardHeader>
          
          <CardContent className="space-y-4">
            {isVerifying ? (
              <div className="flex justify-center items-center py-8">
                <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary"></div>
              </div>
            ) : isSuccess ? (
              <div className="flex flex-col items-center space-y-4">
                <div className="bg-green-100 rounded-full p-3">
                  <CheckCircle2 className="h-12 w-12 text-green-600" />
                </div>
                
                {donation && (
                  <div className="text-center">
                    <p className="font-medium text-lg">
                      {t('payment.donationAmount')}: {donation.amount || donation.attributes?.amount} {donation.currency_code || donation.attributes?.currency_code || t('payment.defaultCurrency')}
                    </p>
                    
                    {(donation.cause || donation.attributes?.cause) && (
                      <p className="text-muted-foreground">
                        {t('payment.donationCause')}: {donation.cause?.title || donation.attributes?.cause?.title}
                      </p>
                    )}
                    
                    <p className="text-sm text-muted-foreground mt-4">
                      {t('payment.donationReference')}: {donation.id || donation.attributes?.id || (donation.transaction_id || donation.attributes?.transaction_id)}
                    </p>
                  </div>
                )}
                
                {certificate && (
                  <div className="mt-4 p-4 border rounded-lg w-full text-center">
                    <h3 className="font-medium">{t('payment.certificateReady')}</h3>
                    <p className="text-sm text-muted-foreground mb-4">
                      {t('payment.certificateDescription')}
                    </p>
                    
                    <Button onClick={handleDownloadCertificate} className="inline-flex">
                      <Download className={isRtl ? "ml-2 h-4 w-4" : "mr-2 h-4 w-4"} />
                      {t('payment.downloadCertificate')}
                    </Button>
                  </div>
                )}
              </div>
            ) : (
              <div className="flex flex-col items-center space-y-4">
                <div className="bg-red-100 rounded-full p-3">
                  <svg className="h-12 w-12 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </div>
                
                <div className="text-center">
                  <p className="font-medium text-lg text-red-600">
                    {t('payment.paymentFailed')}
                  </p>
                  
                  {error && (
                    <p className="text-sm text-muted-foreground mt-2">
                      {error}
                    </p>
                  )}
                </div>
              </div>
            )}
          </CardContent>
          
          <CardFooter className="flex justify-center gap-4">
            <Button onClick={handleReturnHome} variant="outline" className="inline-flex">
              <ArrowLeft className={isRtl ? "ml-2 rotate-180 h-4 w-4" : "mr-2 h-4 w-4"} />
              {t('payment.returnHome')}
            </Button>
            
            {isSuccess && !user && (
              <Button onClick={handleGoToLogin} className="inline-flex">
                <LogIn className={isRtl ? "ml-2 h-4 w-4" : "mr-2 h-4 w-4"} />
                {t('payment.createAccount')}
              </Button>
            )}
          </CardFooter>
        </Card>
      </div>
    </Layout>
  );
}