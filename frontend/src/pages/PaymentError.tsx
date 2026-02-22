import { useNavigate, useSearchParams } from 'react-router-dom';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { XCircle, ArrowLeft } from 'lucide-react';
import { AuthLayout } from '@/components/AuthLayout';
import { useTranslation } from 'react-i18next';

export default function PaymentError() {
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const { t, i18n } = useTranslation();
  const isRtl = i18n.language === 'ar';
  const paymentId = searchParams.get('paymentId');
  
  return (
    <AuthLayout>
      <Card className="w-full max-w-md">
        <CardHeader className="text-center">
          <div className="flex justify-center mb-4">
            <XCircle className="h-12 w-12 text-destructive" />
          </div>
          <CardTitle className="text-2xl font-bold">{t('payment.failed')}</CardTitle>
          <CardDescription>
            {t('payment.couldNotProcess')}
          </CardDescription>
        </CardHeader>
        <CardContent className="text-center">
          <p className="text-muted-foreground mb-4">
            {t('payment.tryAgainOrContact')}
          </p>
          {paymentId && (
            <p className="text-xs text-muted-foreground mt-2">
              {t('payment.errorReference')}: {paymentId}
            </p>
          )}
        </CardContent>
        <CardFooter className="flex justify-center gap-4">
          <Button 
            onClick={() => navigate('/causes')}
            className="flex items-center"
          >
            <ArrowLeft className={isRtl ? "ml-2 h-4 w-4 rotate-180" : "mr-2 h-4 w-4"} />
            {t('cause.backToCauses')}
          </Button>
          <Button 
            variant="outline"
            onClick={() => window.history.back()}
          >
            {t('payment.tryAgain')}
          </Button>
        </CardFooter>
      </Card>
    </AuthLayout>
  );
}