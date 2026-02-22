import { Link } from 'react-router-dom';
import { useState } from 'react';
import { CertificateDialog } from '@/components/CertificateDialog';
import { EditProfileDialog } from '@/components/EditProfileDialog';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Badge } from '@/components/ui/badge';
import { ScrollArea } from '@/components/ui/scroll-area';
import {
  Download,
  Gift,
  Heart,
  History,
  Medal,
  Settings,
  Star,
  Trophy,
  Users,
  User,
  LogIn
} from 'lucide-react';
import { useProfile } from '@/hooks/useProfile';
import { useStatistics } from '@/hooks/useStatistics';
import { useDonations } from '@/hooks/useDonations';
import { useAchievements } from '@/hooks/useAchievements';
import { Layout } from '@/components/Layout';
import { Skeleton } from '@/components/ui/skeleton';
import { useTranslation } from 'react-i18next';
import { useAuth } from '@/hooks/useAuth';

// Map of achievement icons to Lucide icons
const ACHIEVEMENT_ICONS: Record<string, any> = {
  Heart: <Heart />,
  Medal: <Medal />,
  Trophy: <Trophy />,
  Users: <Users />,
  Star: <Star />
};

export default function Profile() {
  const { profile, loading } = useProfile();
  const [selectedDonation, setSelectedDonation] = useState<any>(null);
  const { totalDonated, donationCount, achievementCount, loading: statsLoading } = useStatistics();
  const { donations, loading: donationsLoading, error } = useDonations();
  const { achievements, loading: achievementsLoading } = useAchievements();
  const [activeTab, setActiveTab] = useState<string>('overview');
  const [giftView, setGiftView] = useState<'achievements' | 'causes'>('achievements');
  const { t, i18n } = useTranslation();
  const { user } = useAuth();
  const isRtl = i18n.language === 'ar';

  // Helper function to safely format dates
  const formatDate = (dateString: string) => {
    try {
      const date = new Date(dateString);
      // Check if date is valid
      if (isNaN(date.getTime())) {
        return t('common.invalidDate');
      }
      return date.toLocaleDateString();
    } catch {
      return t('common.invalidDate');
    }
  };

  // Helper function to safely format amounts
  const formatAmount = (amount: number | string) => {
    try {
      const numAmount = typeof amount === 'string' ? parseFloat(amount) : amount;
      
      // Check if amount is valid number
      if (isNaN(numAmount)) {
        return '$0.00';
      }
      
      return `$${numAmount.toLocaleString(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      })}`;
    } catch {
      return '$0.00';
    }
  };

  // If no user is authenticated, show sign-in prompt
  if (!user) {
    return (
      <Layout>
        <div className="flex flex-col items-center justify-center py-12 px-4">
          <Card className="w-full max-w-md">
            <CardContent className="pt-6 text-center">
              <LogIn className="h-16 w-16 mx-auto mb-4 text-primary" />
              <h1 className="text-2xl font-bold mb-2">{t('profile.authRequired')}</h1>
              <p className="mb-6 text-muted-foreground">
                {t('profile.signInPrompt')}
              </p>
              <div className="flex flex-col sm:flex-row justify-center gap-4">
                <Link to="/signin">
                  <Button className="w-full">{t('auth.signIn')}</Button>
                </Link>
                <Link to="/signup">
                  <Button variant="outline" className="w-full">{t('auth.createAccount')}</Button>
                </Link>
              </div>
            </CardContent>
          </Card>
        </div>
      </Layout>
    );
  }

  if (loading || statsLoading || donationsLoading || achievementsLoading) {
    return (
      <Layout>
        <div className="grid grid-cols-12 gap-6">
          <div className="col-span-12 md:col-span-4 lg:col-span-3 space-y-6">
            <Card>
              <CardContent className="pt-6">
                <div className="flex flex-col items-center text-center">
                  <Skeleton className="h-24 w-24 rounded-full" />
                  <Skeleton className="h-6 w-32 mt-4" />
                  <Skeleton className="h-4 w-48 mt-2" />
                  <Skeleton className="h-9 w-full mt-4" />
                </div>
              </CardContent>
            </Card>
          </div>
          <div className="col-span-12 md:col-span-8 lg:col-span-9">
            <Card>
              <CardContent className="pt-6">
                <Skeleton className="h-8 w-48 mb-6" />
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                  {[1, 2, 3].map((i) => (
                    <Card key={i}>
                      <CardContent className="pt-6">
                        <Skeleton className="h-8 w-8 mx-auto mb-2" />
                        <Skeleton className="h-6 w-16 mx-auto mb-1" />
                        <Skeleton className="h-4 w-24 mx-auto" />
                      </CardContent>
                    </Card>
                  ))}
                </div>
              </CardContent>
            </Card>
          </div>
        </div>
      </Layout>
    );
  }

  // If we have a user but no profile (API error or connectivity issue), show error message
  if (!profile && !loading) {
    return (
      <Layout>
        <div className="flex flex-col items-center justify-center py-12 px-4">
          <Card className="w-full max-w-md">
            <CardContent className="pt-6 text-center">
              <User className="h-16 w-16 mx-auto mb-4 text-primary" />
              <h1 className="text-2xl font-bold mb-2">{t('profile.loadError')}</h1>
              <p className="mb-6 text-muted-foreground">
                {t('profile.connectionError')}
              </p>
              <Button onClick={() => window.location.reload()}>
                {t('common.tryAgain')}
              </Button>
            </CardContent>
          </Card>
        </div>
      </Layout>
    );
  }

  return (
    <Layout>
        <div className="grid grid-cols-12 gap-4 sm:gap-6 p-4">
          {/* Profile Sidebar */}
          <div className="col-span-12 md:col-span-4 lg:col-span-3 space-y-4 sm:space-y-6">
            <Card>
              <CardContent className="pt-4 sm:pt-6">
                <div className="flex flex-col items-center text-center">
                  <Avatar className="h-20 w-20 sm:h-24 sm:w-24 mb-4 bg-primary/10">
                    <AvatarImage src={profile?.avatar_url || undefined} />
                    <AvatarFallback>
                      {profile?.first_name?.[0]}{profile?.last_name?.[0]}
                    </AvatarFallback>
                  </Avatar>
                  <h2 className="text-xl font-bold mb-1">
                    {profile?.first_name} {profile?.last_name}
                  </h2>
                  <p className="text-sm text-muted-foreground mb-4">
                    {t('profile.lastUpdated')}: {new Date(profile?.updated_at || '').toLocaleDateString()}
                  </p>
                  <EditProfileDialog />
                </div>
              </CardContent>
            </Card>

            {/* Navigation */}
            <Card>
              <CardContent className="pt-4 sm:pt-6">
                <nav className="space-y-2">
                  <Button
                    variant={activeTab === 'overview' ? 'default' : 'ghost'}
                    className="w-full justify-start"
                    onClick={() => setActiveTab('overview')}
                  >
                    <User className={isRtl ? "h-4 w-4 ml-2" : "h-4 w-4 mr-2"} /> {t('profile.overview')}
                  </Button>
                  <Button
                    variant={activeTab === 'donations' ? 'default' : 'ghost'}
                    className="w-full justify-start"
                    onClick={() => setActiveTab('donations')}
                  >
                    <Heart className={isRtl ? "h-4 w-4 ml-2" : "h-4 w-4 mr-2"} /> {t('profile.myDonations')}
                  </Button>
                  <Button
                    variant={activeTab === 'gifts' ? 'default' : 'ghost'}
                    className="w-full justify-start"
                    onClick={() => setActiveTab('gifts')}
                  >
                    <Gift className={isRtl ? "h-4 w-4 ml-2" : "h-4 w-4 mr-2"} /> {t('profile.giftsRewards')}
                  </Button>
                  <Button
                    variant={activeTab === 'certificates' ? 'default' : 'ghost'}
                    className="w-full justify-start"
                    onClick={() => setActiveTab('certificates')}
                  >
                    <Download className={isRtl ? "h-4 w-4 ml-2" : "h-4 w-4 mr-2"} /> {t('profile.certificates')}
                  </Button>
                </nav>
              </CardContent>
            </Card>
          </div>

          {/* Main Content Area */}
          <div className="col-span-12 md:col-span-8 lg:col-span-9 space-y-4 sm:space-y-6">
            <Card>
              <CardContent className="pt-4 sm:pt-6">
                {activeTab === 'overview' && (
                  <div className="space-y-6">
                    <h2 className="text-xl sm:text-2xl font-bold">{t('profile.overview')}</h2>
                    <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                      <Card>
                        <CardContent className="pt-4 sm:pt-6">
                          <div className="text-center">
                            <Heart className="h-8 w-8 mx-auto mb-2 text-primary" />
                            <div className="text-2xl font-bold">{formatAmount(totalDonated)}</div>
                            <p className="text-sm text-muted-foreground">{t('profile.totalDonated')}</p>
                          </div>
                        </CardContent>
                      </Card>
                      <Card>
                        <CardContent className="pt-4 sm:pt-6">
                          <div className="text-center">
                            <History className="h-8 w-8 mx-auto mb-2 text-primary" />
                            <div className="text-2xl font-bold">{donationCount}</div>
                            <p className="text-sm text-muted-foreground">{t('profile.donationsMade')}</p>
                          </div>
                        </CardContent>
                      </Card>
                      <Card>
                        <CardContent className="pt-4 sm:pt-6">
                          <div className="text-center">
                            <Trophy className="h-8 w-8 mx-auto mb-2 text-primary" />
                            <div className="text-2xl font-bold">{achievementCount}</div>
                            <p className="text-sm text-muted-foreground">{t('profile.achievements')}</p>
                          </div>
                        </CardContent>
                      </Card>
                    </div>
                  </div>
                )}

                {activeTab === 'donations' && (
                  <div className="space-y-6">
                    <h2 className="text-xl sm:text-2xl font-bold">{t('profile.myDonations')}</h2>
                    
                    {/* Show API error messages */}
                    {error && (
                      <div className="p-4 bg-red-50 border border-red-200 rounded-md text-red-600 mb-4">
                        <h3 className="font-semibold mb-1">Error</h3>
                        <p>{error}</p>
                      </div>
                    )}
                    
                    {donations.length === 0 ? (
                      <Card>
                        <CardContent className="pt-4 sm:pt-6 text-center">
                          <Heart className="h-12 w-12 mx-auto mb-4 text-muted-foreground" />
                          <p className="text-muted-foreground">{t('profile.noDonations')}</p>
                          <Link to="/causes" className="mt-4 inline-block">
                            <Button>{t('profile.browseCauses')}</Button>
                          </Link>
                        </CardContent>
                      </Card>
                    ) : (
                      <div className="space-y-4">
                        {donations.map(donation => (
                          <Card key={donation.id}>
                            <CardContent className="pt-4 sm:pt-6">
                              <div className="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
                                <div>
                                  <h3 className="font-semibold">{donation.cause.title}</h3>
                                  <p className="text-sm text-muted-foreground">
                                    {formatDate(donation.created_at)}
                                  </p>
                                </div>
                                <div className="flex items-center justify-between sm:flex-col sm:items-end">
                                  <div className="text-right">
                                    <div className="font-bold">{formatAmount(donation.amount)}</div>
                                    <Badge variant="outline">{donation.status}</Badge>
                                  </div>
                                  <Button 
                                    variant="outline" 
                                    size="sm"
                                    onClick={() => setSelectedDonation(donation)}
                                    className="ml-4 sm:ml-0 sm:mt-2"
                                  >
                                    <Download className={isRtl ? "h-4 w-4 ml-2" : "h-4 w-4 mr-2"} /> {t('profile.certificate')}
                                  </Button>
                                </div>
                              </div>
                            </CardContent>
                          </Card>
                        ))}
                      </div>
                    )}
                  </div>
                )}

                {activeTab === 'gifts' && (
                  <div className="space-y-6">
                    <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                      <h2 className="text-xl sm:text-2xl font-bold">{t('profile.giftsRewards')}</h2>
                      <div className="flex gap-2">
                        <Button
                          variant={giftView === 'achievements' ? 'default' : 'outline'}
                          onClick={() => setGiftView('achievements')}
                          size="sm"
                        >
                          <Trophy className={isRtl ? "h-4 w-4 ml-2" : "h-4 w-4 mr-2"} /> {t('profile.achievements')}
                        </Button>
                        <Button
                          variant={giftView === 'causes' ? 'default' : 'outline'}
                          onClick={() => setGiftView('causes')}
                          size="sm"
                        >
                          <Gift className={isRtl ? "h-4 w-4 ml-2" : "h-4 w-4 mr-2"} /> {t('profile.giftedCauses')}
                        </Button>
                      </div>
                    </div>

                    {giftView === 'achievements' ? (
                      achievements.length === 0 ? (
                        <Card>
                          <CardContent className="pt-4 sm:pt-6 text-center">
                            <Trophy className="h-12 w-12 mx-auto mb-4 text-muted-foreground" />
                            <p className="text-muted-foreground">{t('profile.noAchievements')}</p>
                            <Link to="/causes" className="mt-4 inline-block">
                              <Button>{t('profile.browseCauses')}</Button>
                            </Link>
                          </CardContent>
                        </Card>
                      ) : (
                        <div className="grid gap-4">
                          {achievements.map(achievement => (
                            <Card key={achievement.id}>
                              <CardContent className="pt-4 sm:pt-6">
                                <div className="flex items-center gap-4">
                                  <div className="h-12 w-12 rounded-full bg-primary/10 flex items-center justify-center">
                                    {achievement.achievement_type.icon && (
                                      <div className="h-6 w-6 text-primary">
                                        {ACHIEVEMENT_ICONS[achievement.achievement_type.icon]}
                                      </div>
                                    )}
                                  </div>
                                  <div>
                                    <h3 className="font-semibold">{achievement.achievement_type.title}</h3>
                                    <p className="text-sm text-muted-foreground">
                                      {achievement.achievement_type.description}
                                    </p>
                                    <p className="text-xs text-muted-foreground mt-1">
                                      {t('profile.achievedOn')} {new Date(achievement.achieved_at).toLocaleDateString()}
                                    </p>
                                  </div>
                                </div>
                              </CardContent>
                            </Card>
                          ))}
                        </div>
                      )
                     ) : (
                       <Card>
                         <CardContent className="pt-4 sm:pt-6 text-center">
                           <Gift className="h-12 w-12 mx-auto mb-4 text-muted-foreground" />
                           <p className="text-muted-foreground">{t('profile.giftFeature')}</p>
                           <p className="text-sm text-muted-foreground mt-2">
                             {t('profile.giftFeatureDescription')}
                           </p>
                         </CardContent>
                       </Card>
                     )}
                  </div>
                )}

                {activeTab === 'certificates' && (
                  <div className="space-y-6">
                    <h2 className="text-2xl font-bold">{t('profile.donationCertificates')}</h2>
                    
                    {/* Show API error messages */}
                    {error && (
                      <div className="p-4 bg-red-50 border border-red-200 rounded-md text-red-600 mb-4">
                        <h3 className="font-semibold mb-1">Error</h3>
                        <p>{error}</p>
                      </div>
                    )}
                    
                    {donations.length === 0 ? (
                      <Card>
                        <CardContent className="pt-6 text-center">
                          <Download className="h-12 w-12 mx-auto mb-4 text-muted-foreground" />
                          <p className="text-muted-foreground">{t('profile.noCertificates')}</p>
                          <p className="text-sm text-muted-foreground mt-2">
                            {t('profile.makeDonation')}
                          </p>
                          <Link to="/causes" className="mt-4 inline-block">
                            <Button>
                              {t('profile.browseCauses')}
                            </Button>
                          </Link>
                        </CardContent>
                      </Card>
                    ) : (
                      <div className="grid gap-4">
                        {donations.map(donation => (
                          <Card key={donation.id}>
                            <CardContent className="pt-6">
                              <div className="flex justify-between items-center">
                                <div>
                                  <h3 className="font-semibold">{donation.cause.title}</h3>
                                  <p className="text-sm text-muted-foreground">
                                    {formatDate(donation.created_at)}
                                  </p>
                                </div>
                                <Button 
                                  variant="outline" 
                                  size="sm"
                                  onClick={() => setSelectedDonation(donation)}
                                >
                                  <Download className={isRtl ? "h-4 w-4 ml-2" : "h-4 w-4 mr-2"} /> {t('profile.download')}
                                </Button>
                              </div>
                            </CardContent>
                          </Card>
                        ))}
                      </div>
                    )}
                  </div>
                )}

                {activeTab === 'settings' && (
                  <div className="space-y-6">
                    <h2 className="text-2xl font-bold">{t('profile.settings')}</h2>
                    {/* Add settings content here */}
                  </div>
                )}
                
                {/* Certificate Dialog */}
                {selectedDonation && (
                  <CertificateDialog
                    open={!!selectedDonation}
                    onOpenChange={(open) => !open && setSelectedDonation(null)}
                    donation={selectedDonation}
                    donorName={`${profile?.first_name} ${profile?.last_name}`}
                  />
                )}
              </CardContent>
            </Card>
          </div>
        </div>
    </Layout>
  );
}