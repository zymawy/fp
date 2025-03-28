import React, { useRef } from 'react';
import { useTranslation } from 'react-i18next';
import { format } from 'date-fns';
import { Card, CardContent } from './ui/card';
import { Button } from './ui/button';
import { Printer, Download } from 'lucide-react';
import { useReactToPrint } from 'react-to-print';
import { cn } from '@/lib/utils';

interface CertificateProps {
  recipientName: string;
  amount: number;
  currencyCode: string;
  causeTitle: string;
  donationDate: string | Date;
  certificateId: string;
  className?: string;
  onDownload?: () => void;
}

export function Certificate({
  recipientName,
  amount,
  currencyCode,
  causeTitle,
  donationDate,
  certificateId,
  className,
  onDownload
}: CertificateProps) {
  const { t } = useTranslation();
  const certificateRef = useRef<HTMLDivElement>(null);
  const date = typeof donationDate === 'string' ? new Date(donationDate) : donationDate;
  
  const handlePrint = useReactToPrint({
    content: () => certificateRef.current,
    documentTitle: `Donation_Certificate_${certificateId}`,
  });

  return (
    <div className={cn("flex flex-col w-full", className)}>
      <div className="flex justify-end gap-2 mb-4">
        <Button variant="outline" size="sm" onClick={handlePrint}>
          <Printer className="h-4 w-4 mr-2" />
          {t('common.print')}
        </Button>
        {onDownload && (
          <Button variant="default" size="sm" onClick={onDownload}>
            <Download className="h-4 w-4 mr-2" />
            {t('certificate.downloadCertificate')}
          </Button>
        )}
      </div>

      <Card className="border-2 border-primary" ref={certificateRef}>
        <CardContent className="p-6">
          <div className="flex flex-col items-center text-center">
            <div className="w-24 h-24 mb-4">
              <img 
                src="/logo.svg" 
                alt="In'aam Foundation" 
                className="w-full h-full object-contain"
              />
            </div>
            
            <h1 className="text-3xl font-bold mb-2">{t('certificate.title')}</h1>
            
            <div className="w-full max-w-lg border-t-2 border-b-2 border-primary py-8 my-6">
              <h2 className="text-xl mb-2">{t('certificate.presentedTo')}</h2>
              <p className="text-2xl font-bold mb-6">{recipientName}</p>
              
              <p className="text-lg mb-6">
                {t('certificate.forContribution', { 
                  amount: `${amount} ${currencyCode}`, 
                  causeTitle 
                })}
              </p>
              
              <div className="flex justify-between items-center mt-8">
                <div className="text-left">
                  <p className="text-sm">{t('certificate.date')}</p>
                  <p className="font-medium">{format(date, 'MMMM d, yyyy')}</p>
                </div>
                
                <div className="text-right">
                  <p className="text-sm">{t('certificate.signature')}</p>
                  <p className="font-medium italic">{t('certificate.foundation')}</p>
                </div>
              </div>
            </div>
            
            <p className="text-sm text-muted-foreground">{t('certificate.thankYou')}</p>
            <p className="text-xs text-muted-foreground mt-4">Certificate ID: {certificateId}</p>
          </div>
        </CardContent>
      </Card>
    </div>
  );
} 