import { format } from 'date-fns';
import { Heart } from 'lucide-react';
import { useTranslation } from 'react-i18next';

interface DonationCertificateProps {
  donorName: string;
  amount: number;
  causeTitle: string;
  date: string;
}

export function DonationCertificate({ donorName, amount, causeTitle, date }: DonationCertificateProps) {
  const { t } = useTranslation();
  
  return (
    <div className="w-[800px] h-[600px] bg-white p-16 relative" id="certificate">
      {/* Border Design */}
      <div className="absolute inset-4 border-4 border-primary/20 rounded-lg" />
      <div className="absolute inset-6 border border-primary/10 rounded-lg" />

      {/* Logo and Header */}
      <div className="text-center mb-12">
        <div className="flex items-center justify-center gap-3 mb-4">
          <Heart className="h-8 w-8 text-primary" />
          <h1 className="text-4xl font-bold text-primary">{t('app.name')}</h1>
        </div>
        <h2 className="text-2xl text-gray-600 font-serif">{t('certificate.title')}</h2>
      </div>

      {/* Main Content */}
      <div className="text-center space-y-8">
        <p className="text-lg text-gray-600">{t('certificate.presentedTo')}</p>
        <h3 className="text-3xl font-bold text-gray-800 font-serif">{donorName}</h3>
        <p className="text-lg text-gray-600 max-w-2xl mx-auto leading-relaxed">
          {t('certificate.forContribution', {
            amount: amount.toLocaleString(),
            causeTitle
          })}
        </p>
      </div>

      {/* Footer */}
      <div className="absolute bottom-16 left-16 right-16">
        <div className="grid grid-cols-2 gap-8 mt-16">
          <div className="text-center">
            <div className="h-px w-48 bg-gray-300 mx-auto mb-4" />
            <p className="text-sm text-gray-600">{t('certificate.date')}</p>
            <p className="font-serif">{format(new Date(date), 'MMMM dd, yyyy')}</p>
          </div>
          <div className="text-center">
            <div className="h-px w-48 bg-gray-300 mx-auto mb-4" />
            <p className="text-sm text-gray-600">{t('certificate.signature')}</p>
            <p className="font-serif italic">{t('certificate.foundation')}</p>
          </div>
        </div>
        <div className="text-center mt-8">
          <p className="text-sm text-gray-500">
            {t('certificate.thankYou')}
          </p>
        </div>
      </div>

      {/* Decorative Elements */}
      <div className="absolute top-8 left-8 w-16 h-16 border-t-4 border-l-4 border-primary/20" />
      <div className="absolute top-8 right-8 w-16 h-16 border-t-4 border-r-4 border-primary/20" />
      <div className="absolute bottom-8 left-8 w-16 h-16 border-b-4 border-l-4 border-primary/20" />
      <div className="absolute bottom-8 right-8 w-16 h-16 border-b-4 border-r-4 border-primary/20" />
    </div>
  );
}