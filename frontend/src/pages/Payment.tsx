// 'use client';
import { useState, useEffect } from 'react';
import { useParams, useNavigate, Link, useSearchParams } from 'react-router-dom';
import { useAuth } from '@/hooks/useAuth';
import { useToast } from '@/components/ui/use-toast';
import { 
  executePayment, 
  getPaymentMethods, 
  getCurrencyByLanguage, 
  getCurrencySymbol,
  PaymentMethod 
} from '@/lib/payment';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft, CreditCard, Lock, Gift, Receipt, Heart, InfoIcon, AlertTriangle } from 'lucide-react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Separator } from '@/components/ui/separator';
import { Switch } from '@/components/ui/switch';
import { AuthLayout } from '@/components/AuthLayout';
import { useTranslation } from 'react-i18next';
import { EnhancedProgressBar } from '@/components/EnhancedProgressBar';
import { api } from '@/lib/api';

/*
 * Required translation keys in i18n file:
 * 
 * payment.logs.fetchingMethods: "Fetching payment methods for amount:"
 * payment.logs.methodsReceived: "Payment methods received:"
 * payment.logs.defaultMethod: "Set default payment method:"
 * payment.logs.noValidMethods: "No valid payment methods returned:"
 * payment.logs.fetchMethodsError: "Failed to fetch payment methods:"
 * payment.logs.submissionDetails: "Payment submission details:"
 * payment.logs.notApplicable: "N/A"
 * payment.logs.executingPayment: "Executing payment with final amount:"
 * payment.logs.paymentExecuted: "Payment executed:"
 * payment.logs.redirectingUrl: "Redirecting to payment URL:"
 * payment.logs.invoiceId: "Payment ID/Invoice ID:"
 * payment.logs.invalidResponse: "Invalid payment response structure:"
 * payment.logs.submissionFailed: "Payment submission failed:"
 * payment.logs.methodChanging: "Payment method changing from"
 * payment.logs.to: "to"
 * 
 * payment.errors.missingPaymentUrl: "Invalid payment response: Missing payment URL"
 * payment.errors.invalidResponse: "Invalid payment response"
 * payment.errors.networkError: "Network Error"
 * 
 * payment.donateAmount: "{{amount}}"
 */

export default function Payment() {
  const { id } = useParams();
  const navigate = useNavigate();
  const { user } = useAuth();
  const { toast } = useToast();
  const [searchParams] = useSearchParams();
  const amount = searchParams.get('amount') ? Number(searchParams.get('amount')) : 0;
  const [loading, setLoading] = useState(false);
  const [paymentMethods, setPaymentMethods] = useState<PaymentMethod[]>([]);
  const [loadingMethods, setLoadingMethods] = useState(true);
  const { t, i18n } = useTranslation();
  const isRtl = i18n.language === 'ar';
  const currencyCode = getCurrencyByLanguage();
  const currencySymbol = getCurrencySymbol(currencyCode);

  const [paymentMethod, setPaymentMethod] = useState('card');
  const [isGift, setIsGift] = useState(false);
  const [giftDetails, setGiftDetails] = useState({
    recipientName: '',
    recipientEmail: '',
    message: ''
  });
  const [isAnonymous, setIsAnonymous] = useState(false);
  const [coverFees, setCoverFees] = useState(true);

  // Calculate processing fee (2.9% + $0.30)
  const calculateFee = (donationAmount: number) => {
    // These values should be configurable in a real application
    return (donationAmount * 0.029) + 0.30;
  };

  const totalAmount = coverFees ? amount + calculateFee(amount) : amount;

  // Fetch payment methods
  useEffect(() => {
    const fetchPaymentMethods = async () => {
      try {
        setLoadingMethods(true);

        const methods = await getPaymentMethods(totalAmount);

        if (Array.isArray(methods) && methods.length > 0) {
          setPaymentMethods(methods);
          // Select the first payment method by default
          const defaultMethod = String(methods[0].PaymentMethodId);
          setPaymentMethod(defaultMethod);
        } else {
          setPaymentMethods([]);
        }
      } catch {
        setPaymentMethods([]);
      } finally {
        setLoadingMethods(false);
      }
    };

    fetchPaymentMethods();
  }, [totalAmount, t]);

  if (loadingMethods) {
    return (
      <AuthLayout>
        <div className="w-full max-w-3xl mx-auto px-4">
          <Card className="border-none shadow-md">
            <CardContent className="pt-6">
              <div className="flex flex-col items-center justify-center py-12">
                <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary mb-4"></div>
                <p className="text-muted-foreground">{t('payment.loadingMethods')}</p>
              </div>
            </CardContent>
          </Card>
        </div>
      </AuthLayout>
    );
  }

  if (!loadingMethods && paymentMethods.length === 0) {
    return (
      <AuthLayout>
        <div className="w-full max-w-3xl mx-auto px-4">
          <Card className="border-none shadow-md">
            <CardContent className="pt-6">
              <div className="text-center py-8">
                <AlertTriangle className="h-12 w-12 text-destructive mx-auto mb-4" />
                <h2 className="text-xl font-semibold mb-2">{t('payment.methodsUnavailable')}</h2>
                <p className="text-muted-foreground mb-6">
                  {t('payment.tryAgainLater')}
                </p>
                <Link to={`/causes/${id}`}>
                  <Button variant="default">
                    <ArrowLeft className={isRtl ? "ml-2 rotate-180 h-4 w-4" : "mr-2 h-4 w-4"} /> 
                    {t('cause.backToCause')}
                  </Button>
                </Link>
              </div>
            </CardContent>
          </Card>
        </div>
      </AuthLayout>
    );
  }

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    
    if (!user) {
      navigate('/signin');
      toast({
        variant: "destructive",
        title: t('auth.requiredTitle'),
        description: t('auth.requiredDescription'),
      });
      return;
    }

    if (!paymentMethod) {
      toast({
        variant: "destructive",
        title: t('payment.errorTitle'),
        description: t('payment.selectMethodError'),
      });
      return;
    }

    setLoading(true);
    try {
      // Calculate fees if covered
      const processingFee = coverFees ? calculateFee(amount) : 0;
      const finalAmount = coverFees ? amount + processingFee : amount;
      
      const response = await executePayment({
        amount: finalAmount,
        customerName: user.firstName && user.lastName ? `${user.firstName} ${user.lastName}` : user.email,
        customerEmail: user.email,
        customerPhone: user.phoneNumber || undefined,
        paymentMethod,
        callbackUrl: `${window.location.origin}/payment/success`,
        errorUrl: `${window.location.origin}${t('routes.paymentError')}`,
        currencyIso: currencyCode, // Pass the currency code
        // Additional donation options
        isGift,
        recipientName: isGift ? giftDetails.recipientName : undefined,
        recipientEmail: isGift ? giftDetails.recipientEmail : undefined,
        giftMessage: isGift ? giftDetails.message : undefined,
        isAnonymous,
        coverFees,
        processingFee: coverFees ? processingFee : undefined,
        causeId: id!, // From URL params
        userId: user.id, // From auth context
      });

    //   console.log(t('payment.logs.paymentExecuted'), response);
      
    //   const { data: responseData } = response;

    //   console.log(t('payment.logs.paymentExecuted'), responseData);
    //   // Check if the response has a PaymentURL property
    //   if (responseData && responseData.PaymentURL) {
    //     // Log the URL we're redirecting to
    //     console.log(t('payment.logs.redirectingUrl'), responseData.PaymentURL);
        
    //     // Get the invoice ID for reference
    //     const invoiceId = responseData.InvoiceId || responseData.PaymentId;
    //     console.log(t('payment.logs.invoiceId'), invoiceId);
        
    //     // Redirect to MyFatoorah payment page
    //     window.location.href = responseData.PaymentURL + '&invoiceId=' + invoiceId;
    //   } else {
    //     // Log detailed information about the response
    //     console.error(t('payment.logs.invalidResponse'), response);
    //     throw new Error(t('payment.errors.missingPaymentUrl'));
    //   }
    // } catch (error) {
    //   console.error(t('payment.logs.submissionFailed'), error);
      
    //   // Provide a more specific error message based on the error
    //   let errorMessage = '';
    //   if (error instanceof Error) {
    //     errorMessage = error.message;
    //     // Check for specific error patterns to provide better user guidance
    //     if (error.message.includes(t('payment.errors.invalidResponse'))) {
    //       errorMessage = t('payment.urlMissingError');
    //     } else if (error.message.includes(t('payment.errors.networkError'))) {
    //       errorMessage = t('payment.networkError');
    //     }
    //   } else {
    //     errorMessage = t('payment.errorDescription');
    //   }
      
    //   toast({
    //     variant: "destructive",
    //     title: t('payment.errorTitle'),
    //     description: errorMessage,
    //   });
    } finally {
      setLoading(false);
    }
  };

  const handlePaymentMethodChange = (value: string) => {
    setPaymentMethod(value);
  };

  if (!amount) {
    navigate(`/causes/${id}`);
    return null;
  }

  return (
    <AuthLayout>
      <div className="w-full max-w-3xl mx-auto px-4 py-6">
          <Card className="border-none shadow-md overflow-hidden">
            <CardHeader className="space-y-3 bg-muted/50 border-b">
              <div className="flex items-center justify-between">
                <Link
                  to={`/causes/${id}`}
                  className="text-sm text-primary hover:underline inline-flex items-center"
                >
                  <ArrowLeft className={isRtl ? "ml-2 rotate-180 h-4 w-4" : "mr-2 h-4 w-4"} /> 
                  {t('cause.backToCause')}
                </Link>
                <div className="flex items-center text-sm text-muted-foreground">
                  <Lock className="h-4 w-4 mr-2" /> {t('payment.securePayment')}
                </div>
              </div>
              <CardTitle className="text-2xl font-bold text-center">{t('payment.completeYourDonation')}</CardTitle>
              <CardDescription className="text-center text-sm sm:text-base">
                {t('payment.supportMakesDifference')}
              </CardDescription>
            </CardHeader>
            <form onSubmit={handleSubmit}>
              <CardContent className="space-y-6 pt-6">
                {/* Donation Amount Summary */}
                <div className="bg-primary/5 p-4 rounded-lg">
                  <div className="flex items-center justify-between mb-3">
                    <h3 className="font-medium text-primary flex items-center">
                      <Heart className="h-4 w-4 mr-2" />
                      {t('payment.donationSummary')}
                    </h3>
                    <span className="text-xl font-bold">{currencySymbol}{totalAmount.toFixed(2)}</span>
                  </div>
                  <EnhancedProgressBar 
                    value={totalAmount} 
                    max={totalAmount * 1.5} 
                    showPercentage={false}
                    showValues={false}
                    className="mb-2" 
                  />
                  <p className="text-sm text-muted-foreground text-center mt-2">
                    {t('payment.thanksForSupport')}
                  </p>
                </div>

                {/* Gift Options */}
                <div className="space-y-4">
                  <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                      <Gift className="h-5 w-5 text-primary" />
                      <Label htmlFor="isGift" className="font-medium">{t('payment.makeGift')}</Label>
                    </div>
                    <Switch
                      id="isGift"
                      checked={isGift}
                      onCheckedChange={setIsGift}
                    />
                  </div>

                  {isGift && (
                    <div className="space-y-4 pl-8 border-l-2 border-primary/20">
                      <div className="space-y-2">
                        <Label htmlFor="recipientName">{t('payment.recipientName')}</Label>
                        <Input
                          id="recipientName"
                          value={giftDetails.recipientName}
                          onChange={(e) => setGiftDetails(prev => ({ ...prev, recipientName: e.target.value }))}
                          placeholder={t('payment.enterRecipientName')}
                          className="h-10 sm:h-12"
                        />
                      </div>
                      <div className="space-y-2">
                        <Label htmlFor="recipientEmail">{t('payment.recipientEmail')}</Label>
                        <Input
                          id="recipientEmail"
                          type="email"
                          value={giftDetails.recipientEmail}
                          onChange={(e) => setGiftDetails(prev => ({ ...prev, recipientEmail: e.target.value }))}
                          placeholder={t('payment.enterRecipientEmail')}
                          className="h-10 sm:h-12"
                        />
                      </div>
                      <div className="space-y-2">
                        <Label htmlFor="giftMessage">{t('payment.giftMessage')}</Label>
                        <Input
                          id="giftMessage"
                          value={giftDetails.message}
                          onChange={(e) => setGiftDetails(prev => ({ ...prev, message: e.target.value }))}
                          placeholder={t('payment.addPersonalMessage')}
                          className="h-10 sm:h-12"
                        />
                      </div>
                    </div>
                  )}
                </div>

                <Separator />

                {/* Donation Preferences */}
                <div className="space-y-4">
                  <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                      <Receipt className="h-5 w-5 text-primary" />
                      <div>
                        <Label htmlFor="coverFees" className="font-medium">{t('payment.coverFees')}</Label>
                        <p className="text-xs text-muted-foreground">{t('payment.coverFeesDescription')}</p>
                      </div>
                    </div>
                    <Switch
                      id="coverFees"
                      checked={coverFees}
                      onCheckedChange={setCoverFees}
                    />
                  </div>
                  <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                      <InfoIcon className="h-5 w-5 text-primary" />
                      <div>
                        <Label htmlFor="anonymous" className="font-medium">{t('payment.anonymous')}</Label>
                        <p className="text-xs text-muted-foreground">{t('payment.anonymousDescription')}</p>
                      </div>
                    </div>
                    <Switch
                      id="anonymous"
                      checked={isAnonymous}
                      onCheckedChange={setIsAnonymous}
                    />
                  </div>
                </div>

                <Separator />

                {/* Payment Method Selection */}
                <div className="space-y-4">
                  <h3 className="text-lg font-medium">{t('payment.paymentMethod')}</h3>
                  
                  {paymentMethods.length > 0 ? (
                    <div className="grid grid-cols-1 gap-3">
                      {paymentMethods.map((method) => {
                        const methodId = String(method.PaymentMethodId);
                        const isSelected = paymentMethod === methodId;
                        
                        return (
                          <div 
                            key={methodId} 
                            className={`relative border rounded-lg p-4 transition-colors cursor-pointer hover:bg-primary/5 ${
                              isSelected ? 'border-primary bg-primary/5' : 'border-border'
                            }`}
                            onClick={() => handlePaymentMethodChange(methodId)}
                          >
                            <div className="flex items-center">
                              <div className="mr-3">
                                <input
                                  type="radio"
                                  id={`method-${methodId}`}
                                  name="paymentMethod"
                                  value={methodId}
                                  checked={isSelected}
                                  onChange={() => handlePaymentMethodChange(methodId)}
                                  className="h-4 w-4 text-primary focus:ring-primary border-gray-300"
                                />
                              </div>
                              <div className="flex items-center gap-3 w-full">
                                {method.ImageUrl && (
                                  <img 
                                    src={method.ImageUrl} 
                                    alt={method.PaymentMethodEn || t('payment.paymentMethod')} 
                                    className="h-8 w-auto object-contain" 
                                    onError={(e) => {
                                      // If image fails to load, hide it
                                      e.currentTarget.style.display = 'none';
                                    }}
                                  />
                                )}
                                <span className={`font-medium ${isSelected ? 'text-primary' : ''}`}>
                                  {isRtl && method.PaymentMethodAr 
                                    ? method.PaymentMethodAr 
                                    : method.PaymentMethodEn || t('payment.unknownMethod')}
                                </span>
                              </div>
                            </div>
                          </div>
                        );
                      })}
                    </div>
                  ) : (
                    <div className="p-4 border rounded-md text-center text-muted-foreground">
                      <p>{t('payment.noMethodsAvailable')}</p>
                    </div>
                  )}
                </div>
              </CardContent>
              <CardFooter className="flex flex-col space-y-4 bg-muted/30 border-t pt-6">
                {/* Donation Summary */}
                <div className="w-full space-y-2 bg-background p-4 rounded-lg shadow-sm">
                  <div className="flex justify-between text-sm">
                    <span>{t('payment.donationAmount')}:</span>
                    <span>{currencySymbol}{amount.toFixed(2)}</span>
                  </div>
                  {coverFees && (
                    <div className="flex justify-between text-sm text-muted-foreground">
                      <span>{t('payment.processingFee')}:</span>
                      <span>{currencySymbol}{calculateFee(amount).toFixed(2)}</span>
                    </div>
                  )}
                  <Separator className="my-2" />
                  <div className="flex justify-between font-medium">
                    <span>{t('payment.totalAmount')}:</span>
                    <span>{currencySymbol}{totalAmount.toFixed(2)}</span>
                  </div>
                </div>

                <Button
                  type="submit"
                  className="w-full h-12 text-lg"
                  disabled={loading || !paymentMethod}
                  size="lg"
                >
                  {loading ? t('payment.processing') : t('payment.donateAmount', { amount: `${currencySymbol}${totalAmount.toFixed(2)} ${currencyCode}` })}
                </Button>

                <Alert className="mt-4">
                  <AlertDescription className="text-xs text-center text-muted-foreground">
                    {t('payment.termsAgreement')}
                  </AlertDescription>
                </Alert>
              </CardFooter>
            </form>
          </Card>
      </div>
    </AuthLayout>
  );
}