import { useParams, Link, useNavigate } from 'react-router-dom';
import { Button } from '@/components/ui/button';
import { Progress } from '@/components/ui/progress';
import { Card, CardContent } from '@/components/ui/card';
import { ShareDialog } from '@/components/ShareDialog';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Heart, Users, Calendar, ArrowLeft, AlertTriangle, DollarSign, Share2 } from 'lucide-react';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Input } from '@/components/ui/input';
import { Layout } from '@/components/Layout';
import { useState, useEffect } from 'react';
import { EnhancedProgressBar } from '@/components/EnhancedProgressBar';
import { api } from '@/lib/api';
import { useTranslation } from 'react-i18next';
import { DonationProgressLive } from '@/components/DonationProgressLive';
import { useCauseRealtime } from '@/hooks/useCauseRealtime';

// Define the Cause interface based on API response
interface Cause {
  id: string;
  title: string;
  description: string;
  longDescription?: string;
  imageUrl: string;
  raisedAmount: number;
  goalAmount: number;
  donorCount?: number;
  categoryId: string;
  category?: {
    id: string;
    name: string;
    slug: string;
  };
  status: string;
  startDate?: string;
  endDate?: string;
  createdAt: string;
  updatedAt: string;
  slug?: string;
}

// Define the CauseUpdate interface
interface CauseUpdate {
  id: string;
  causeId: string;
  title: string;
  content: string;
  createdAt: Date | string;
  updatedAt: Date | string;
}

// Extend the Cause type to include updates
interface CauseWithUpdates extends Cause {
  updates?: CauseUpdate[];
  donor_count?: number;
  donors_count?: number;
  unique_donors?: number;
}

const SUGGESTED_AMOUNTS = [10, 25, 50, 100, 250, 500];

export default function CauseDetail() {
  const { id } = useParams();
  const navigate = useNavigate();
  const [cause, setCause] = useState<CauseWithUpdates | null>(null);
  const [loading, setLoading] = useState(true);
  const [donationAmount, setDonationAmount] = useState<number | ''>('');
  const [customAmount, setCustomAmount] = useState(false);
  const { t, i18n } = useTranslation();
  const isRtl = i18n.language === 'ar';

  useEffect(() => {
    const fetchCause = async () => {
      if (!id) return;
      try {
        const data = await api.causes.getById(id) as CauseWithUpdates;
        setCause(data);
      } catch {
        // Error is handled by the loading/null check in the render
      } finally {
        setLoading(false);
      }
    };

    fetchCause();
  }, [id]);

  const handleDonate = () => {
    if (!donationAmount) {
      return;
    }

    if (!cause?.id) {
      return;
    }

    navigate(`/causes/${cause.id}/donate?amount=${donationAmount}`);
  };

  // Update the component to calculate donor count from all possible fields
  const donorCount = 
    (typeof cause?.donorCount === 'number' && !isNaN(cause.donorCount)) ? cause.donorCount :
    (typeof cause?.donor_count === 'number' && !isNaN(cause.donor_count)) ? cause.donor_count :
    (typeof cause?.donors_count === 'number' && !isNaN(cause.donors_count)) ? cause.donors_count :
    (typeof cause?.unique_donors === 'number' && !isNaN(cause.unique_donors)) ? cause.unique_donors :
    0;

  if (loading) {
    return (
      <Layout>
        <div className="flex items-center justify-center min-h-[60vh]">
          <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
        </div>
      </Layout>
    );
  }

  if (!cause) {
    return (
      <Layout>
        <div className="max-w-md mx-auto mt-8">
          <Alert variant="destructive">
            <AlertTriangle className="h-5 w-5" />
            <AlertDescription className="mt-2">
              {t('cause.notFound')}
            </AlertDescription>
          </Alert>
          <div className="mt-4 text-center">
            <Link to="/causes">
              <Button variant="outline">
                <ArrowLeft className={isRtl ? "ml-2 h-4 w-4 rotate-180" : "mr-2 h-4 w-4"} /> {t('nav.causes')}
              </Button>
            </Link>
          </div>
        </div>
      </Layout>
    );
  }

  return (
    <Layout>
        <Link to="/causes" className="inline-flex items-center text-primary hover:underline mb-4 sm:mb-6 px-4">
          <ArrowLeft className={isRtl ? "ml-2 h-4 w-4 rotate-180" : "mr-2 h-4 w-4"} /> {t('cause.backToCauses')}
        </Link>

        <div className="grid lg:grid-cols-3 gap-6 lg:gap-8 px-4">
          <div className="lg:col-span-2">
            <img 
              src={cause.imageUrl} 
              alt={cause.title}
              className="w-full h-[300px] sm:h-[400px] object-cover rounded-lg mb-4 sm:mb-6"
            />
            
            <Tabs defaultValue="about" className="w-full">
              <TabsList className="grid w-full grid-cols-2">
                <TabsTrigger value="about">{t('cause.about')}</TabsTrigger>
                <TabsTrigger value="updates">{t('cause.updates')}</TabsTrigger>
              </TabsList>
              <TabsContent value="about" className="mt-4 sm:mt-6">
                <h2 className="text-2xl font-bold mb-4">{t('cause.about')}</h2>
                <p className="text-muted-foreground">{cause.longDescription}</p>
              </TabsContent>
              <TabsContent value="updates" className="mt-4 sm:mt-6">
                <h2 className="text-2xl font-bold mb-4">{t('cause.latestUpdates')}</h2>
                {cause.updates && cause.updates.length > 0 ? (
                  <div className="space-y-4">
                    {cause.updates.map((update: CauseUpdate) => (
                      <Card key={update.id}>
                        <CardContent className="pt-4 sm:pt-6">
                          <div className="flex items-center gap-2 text-sm text-muted-foreground mb-2">
                            <Calendar className="h-4 w-4" />
                            {typeof update.createdAt === 'string' 
                              ? new Date(update.createdAt).toLocaleDateString() 
                              : update.createdAt.toLocaleDateString()}
                          </div>
                          <h3 className="font-semibold mb-2">{update.title}</h3>
                          <p className="text-muted-foreground">{update.content}</p>
                        </CardContent>
                      </Card>
                    ))}
                  </div>
                ) : (
                  <div className="text-center py-6">
                    <p className="text-muted-foreground">{t('cause.noUpdates')}</p>
                  </div>
                )}
              </TabsContent>
            </Tabs>
          </div>

          <div className="lg:col-span-1">
            <Card>
              <CardContent className="pt-4 sm:pt-6">
                <div className="space-y-6">
                  <div>
                    <h1 className="text-2xl font-bold mb-2">{cause.title}</h1>
                    <p className="text-muted-foreground">{cause.description}</p>
                  </div>

                  <div className="space-y-4">
                    <DonationProgressLive 
                      causeId={cause.id}
                      initialProgress={Math.min(Math.round((cause.raisedAmount / cause.goalAmount) * 100), 100)}
                      initialRaisedAmount={cause.raisedAmount}
                      targetAmount={cause.goalAmount}
                    />
                    <div className="flex justify-between text-sm">
                      <span className="font-medium">${cause.raisedAmount.toLocaleString()} {t('cause.raised')}</span>
                      <span className="text-muted-foreground">{t('cause.of')} ${cause.goalAmount.toLocaleString()}</span>
                    </div>
                  </div>

                  <div className="mb-6">
                    <div className="flex justify-between items-center mb-2">
                      <div className="flex items-center">
                        <Users className="text-primary mr-1.5 h-5 w-5" />
                        <span className="font-semibold">{donorCount} {t('causes.donors')}</span>
                      </div>
                      <div className="flex items-center">
                        <Calendar className="text-primary mr-1.5 h-5 w-5" />
                        <span className="font-semibold">
                          {new Date(cause.endDate || '').toLocaleDateString(undefined, {
                            month: 'short',
                            day: 'numeric'
                          })}
                        </span>
                      </div>
                    </div>
                  </div>

                  <div className="space-y-4">
                    <div className="flex flex-wrap gap-2 mb-4">
                      {[10, 25, 50, 100, 250].map((amount) => (
                        <Button
                          key={amount}
                          variant={donationAmount === amount ? "default" : "outline"}
                          className="flex-1 min-w-[80px]"
                          onClick={() => setDonationAmount(amount)}
                        >
                          ${amount}
                        </Button>
                      ))}
                      <Button
                        variant={customAmount ? "default" : "outline"}
                        className="flex-1 min-w-[120px]"
                        onClick={() => {
                          setCustomAmount(true);
                          setDonationAmount(0);
                        }}
                      >
                        {t('donation.customAmount')}
                      </Button>
                    </div>
                    {customAmount && (
                      <div className="mb-4">
                        <Input
                          type="number"
                          placeholder={t('donation.enterAmount')}
                          value={donationAmount || ''}
                          onChange={(e) => setDonationAmount(Number(e.target.value))}
                          className="w-full"
                          min={1}
                        />
                      </div>
                    )}
                    <Button
                      className="w-full h-12 text-lg"
                      disabled={!donationAmount || donationAmount <= 0}
                      onClick={handleDonate}
                      type="button"
                    >
                      {donationAmount && donationAmount > 0 
                        ? `${t('donation.donate')} $${donationAmount}` 
                        : t('donation.selectAmount')}
                      <Heart className={isRtl ? "mr-2 h-5 w-5" : "ml-2 h-5 w-5"} />
                    </Button>
                    
                    <ShareDialog causeTitle={cause.title} causeId={cause.id} />
                  </div>
                </div>
              </CardContent>
            </Card>
          </div>
        </div>
    </Layout>
  );
}