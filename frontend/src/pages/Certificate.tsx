import React, { useEffect, useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { Certificate as CertificateComponent } from '@/components/Certificate';
import { Loader2 } from 'lucide-react';
import { toast } from '@/components/ui/use-toast';
import { fetchApi } from '@/lib/api';
import { Layout } from '@/components/Layout';

type CertificateData = {
  id: string;
  donationId: string;
  recipientName: string;
  amount: number;
  currencyCode: string;
  causeTitle: string;
  donationDate: string;
  certificateUrl: string;
  imageUrl?: string;
};

type ApiResponse = {
  data: CertificateData;
  error?: string;
};

export default function CertificatePage() {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const { t } = useTranslation();
  const [certificateData, setCertificateData] = useState<CertificateData | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const fetchCertificate = async () => {
      if (!id) {
        setError('Certificate ID is missing');
        setLoading(false);
        return;
      }

      try {
        setLoading(true);
        const response = await fetchApi<ApiResponse>(`/certificates/${id}`, {
          method: 'GET',
        });

        if (response.error) {
          throw new Error(response.error);
        }

        setCertificateData(response.data);
      } catch (err) {
        console.error('Error fetching certificate:', err);
        setError(t('errors.failedToLoadCertificate'));
        toast({
          variant: 'destructive',
          title: t('errors.error'),
          description: t('errors.failedToLoadCertificate'),
        });
      } finally {
        setLoading(false);
      }
    };

    fetchCertificate();
  }, [id, t]);

  const handleDownload = () => {
    // In a real implementation, we would generate a PDF and trigger download
    // For now, we'll just print the page
    window.print();
  };

  if (loading) {
    return (
      <Layout>
        <div className="flex items-center justify-center h-[60vh]">
          <Loader2 className="h-8 w-8 animate-spin text-primary" />
        </div>
      </Layout>
    );
  }

  if (error || !certificateData) {
    return (
      <Layout>
        <div className="flex flex-col items-center justify-center h-[60vh] text-center">
          <h1 className="text-2xl font-bold mb-4">{t('errors.certificateNotFound')}</h1>
          <p className="text-muted-foreground mb-6">{error || t('errors.invalidCertificateId')}</p>
          <button
            onClick={() => navigate('/')}
            className="text-primary hover:underline"
          >
            {t('common.backToHome')}
          </button>
        </div>
      </Layout>
    );
  }

  return (
    <Layout>
      <div className="container max-w-3xl py-8">
        <h1 className="text-3xl font-bold mb-6 text-center">
          {t('certificate.title')}
        </h1>
        
        <CertificateComponent
          recipientName={certificateData.recipientName}
          amount={certificateData.amount}
          currencyCode={certificateData.currencyCode}
          causeTitle={certificateData.causeTitle}
          donationDate={certificateData.donationDate}
          certificateId={certificateData.id}
          onDownload={handleDownload}
        />
      </div>
    </Layout>
  );
} 