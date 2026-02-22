import { useState } from 'react';
import { Dialog, DialogContent } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { DonationCertificate } from './DonationCertificate';
import html2canvas from 'html2canvas';
import { useTranslation } from 'react-i18next';

interface CertificateDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  donation: {
    amount: number;
    cause: {
      title: string;
    };
    created_at: string;
  };
  donorName: string;
}

export function CertificateDialog({ open, onOpenChange, donation, donorName }: CertificateDialogProps) {
  const [downloading, setDownloading] = useState(false);
  const { t } = useTranslation();

  const handleDownload = async () => {
    setDownloading(true);
    try {
      const certificate = document.getElementById('certificate');
      if (!certificate) return;

      const canvas = await html2canvas(certificate, {
        scale: 2,
        backgroundColor: '#ffffff',
      });

      const link = document.createElement('a');
      link.download = `donation-certificate-${new Date().getTime()}.png`;
      link.href = canvas.toDataURL('image/png');
      link.click();
    } catch {
      // Certificate generation failed silently - user can try again
    } finally {
      setDownloading(false);
    }
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-[850px]">
        <div className="space-y-4">
          <DonationCertificate
            donorName={donorName}
            amount={donation.amount}
            causeTitle={donation.cause.title}
            date={donation.created_at}
          />
          <div className="flex justify-end">
            <Button onClick={handleDownload} disabled={downloading}>
              {downloading ? t('certificate.downloading') : t('certificate.downloadCertificate')}
            </Button>
          </div>
        </div>
      </DialogContent>
    </Dialog>
  );
}