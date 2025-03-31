import { ArrowRight, Building2, Users2, Briefcase, BarChart3, ShieldCheck, Globe2, Heart, Target } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Link } from 'react-router-dom';
import { Carousel, CarouselContent, CarouselItem, CarouselNext, CarouselPrevious } from "@/components/ui/carousel";
import { useEffect, useState } from 'react';
import { Card, CardContent } from "@/components/ui/card";
import { useCauses, Cause } from '@/hooks/useCauses';
import { Layout } from '@/components/Layout';
import { CauseCard } from '@/components/CauseCard';
import InfiniteScroll from '@/components/ui/infinite-scroll';
import { useTranslation } from 'react-i18next';
import { api } from '@/lib/api';
import { useAuth } from '@/hooks/useAuth';

// We'll be explicit with all the properties we use in the component
interface ExtendedCause extends Cause {
  sliderSubtitle?: string;
  sliderButtonText?: string;
}

// Type for the partner data
interface Partner {
  id: string;
  name: string;
  logo: string;
  website?: string;
  is_active: boolean;
  order?: number;
}

export default function Home() {
  const [activeSlide, setActiveSlide] = useState(0);
  const [featuredCauses, setFeaturedCauses] = useState<ExtendedCause[]>([]);
  const [partners, setPartners] = useState<Partner[]>([]);
  const [loading, setLoading] = useState(true);
  const { t, i18n } = useTranslation();
  const { user } = useAuth();
  
  // Use the useCauses hook for regular causes
  const { causes, hasMore, loading: causesLoading, initialLoading, loadMore } = useCauses();
  
  // Use the fetched causes or empty array if nothing is returned
  const displayedCauses = causes.length > 0 ? causes : [];
  
  // Check RTL direction
  const isRtl = i18n.language === 'ar';

  // Fetch featured causes for the hero slider
  useEffect(() => {
    const fetchFeaturedCauses = async () => {
      try {
        setLoading(true);
        const featuredData = await api.causes.getFeatured();
        // Ensure all required fields exist in the returned data
        const processedCauses = featuredData.map((cause: any) => ({
          id: cause.id,
          title: cause.title,
          description: cause.description,
          imageUrl: cause.featured_image || cause.image_url,
          category: cause.category ? {
            id: cause.category.id,
            name: cause.category.name,
            slug: cause.category.slug
          } : undefined,
          sliderSubtitle: cause.slider_subtitle,
          sliderButtonText: cause.slider_button_text
        })) as ExtendedCause[];
        setFeaturedCauses(processedCauses);
      } catch (error) {
        console.error('Error fetching featured causes:', error);
      } finally {
        setLoading(false);
      }
    };

    fetchFeaturedCauses();
  }, []);

  // Fetch partners
  useEffect(() => {
    const fetchPartners = async () => {
      try {
        const partnersData = await api.partners.list();
        setPartners(partnersData);
      } catch (error) {
        console.error('Error fetching partners:', error);
      }
    };

    fetchPartners();
  }, []);

  // Auto-rotate the hero slider
  useEffect(() => {
    if (featuredCauses.length === 0) return;
    
    const interval = setInterval(() => {
      setActiveSlide((prev) => (prev + 1) % featuredCauses.length);
    }, 5000);
    
    return () => clearInterval(interval);
  }, [featuredCauses.length]);

  // Update Embla Carousel API to sync with active slide
  const [emblaApi, setEmblaApi] = useState<any>(null);

  // Sync the embla carousel with active slide
  useEffect(() => {
    if (emblaApi && featuredCauses.length > 0) {
      emblaApi.scrollTo(activeSlide);
    }
  }, [activeSlide, emblaApi, featuredCauses.length]);

  // Sync the active slide with the current slide from Embla
  useEffect(() => {
    if (emblaApi) {
      const onSelect = () => {
        setActiveSlide(emblaApi.selectedScrollSnap());
      };
      
      emblaApi.on('select', onSelect);
      return () => {
        emblaApi.off('select', onSelect);
      };
    }
  }, [emblaApi]);

  return (
    <Layout>
      {/* Hero Section with Featured Causes */}
      <section className="relative h-[70vh] max-h-[800px] min-h-[500px] w-full overflow-hidden">
        {featuredCauses.length > 0 ? (
          <Carousel className="w-full h-full rounded-b-lg" setApi={setEmblaApi} opts={{ loop: true }}>
            <CarouselContent>
              {featuredCauses.map((cause, index) => (
                <CarouselItem key={cause.id} className="relative w-full h-full">
                  <div 
                    className="absolute inset-0 bg-cover bg-center bg-no-repeat transition-transform duration-1000 animate-ken-burns"
                    style={{ 
                      backgroundImage: `url(${cause.imageUrl})`,
                      backgroundSize: 'cover',
                      backgroundPosition: 'center 30%'
                    }}
                  >
                    <div className="absolute inset-0 bg-gradient-to-r from-black/80 via-black/50 to-transparent dark:from-black/90" />
                  </div>
                  <div className="relative h-full flex items-center animate-fade-in">
                    <div className="container mx-auto px-6 sm:px-8 lg:px-12 py-8 text-white">
                      <div className="max-w-4xl">
                        <span className="inline-block px-4 py-2 rounded-full bg-primary/20 text-white text-xs sm:text-sm font-medium mb-4 sm:mb-6 backdrop-blur-sm">
                          {cause.sliderSubtitle || cause.category?.name || `Project ${index + 1}`}
                        </span>
                        <h1 className="text-3xl sm:text-5xl md:text-6xl font-bold mb-4 sm:mb-6 leading-tight animate-slide-up">
                          {cause.title}
                        </h1>
                        <p className="text-base sm:text-lg md:text-xl mb-6 sm:mb-8 max-w-2xl text-white/90 animate-slide-up-delayed">
                          {cause.description}
                        </p>
                        <Link to={`/causes/${cause.id}`}>
                          <Button 
                            size="lg" 
                            variant="default" 
                            className="bg-white text-primary hover:bg-white/90 h-12 sm:h-14 px-6 sm:px-8 text-base sm:text-lg shadow-lg hover:shadow-xl transition-all duration-300 animate-slide-up-more-delayed"
                          >
                            {cause.sliderButtonText || "Learn More"} 
                            <ArrowRight className={isRtl ? "mr-2 h-5 w-5 rotate-180" : "ml-2 h-5 w-5"} />
                          </Button>
                        </Link>
                      </div>
                    </div>
                  </div>
                </CarouselItem>
              ))}
            </CarouselContent>
            <CarouselPrevious className="left-5 sm:left-10 h-10 w-10 sm:h-12 sm:w-12 opacity-70 hover:opacity-100 transition-opacity bg-black/30 hover:bg-black/50 backdrop-blur-sm border-white/20" />
            <CarouselNext className="right-5 sm:right-10 h-10 w-10 sm:h-12 sm:w-12 opacity-70 hover:opacity-100 transition-opacity bg-black/30 hover:bg-black/50 backdrop-blur-sm border-white/20" />
            
            {/* Carousel Indicators */}
            <div className="absolute bottom-4 left-0 right-0 flex justify-center gap-2">
              {featuredCauses.map((_, index) => (
                <button
                  key={index}
                  className={`w-3 h-3 rounded-full transition-all ${
                    index === activeSlide ? 'bg-white scale-110' : 'bg-white/40 hover:bg-white/60'
                  }`}
                  onClick={() => setActiveSlide(index)}
                  aria-label={`Go to slide ${index + 1}`}
                />
              ))}
            </div>
          </Carousel>
        ) : (
          <div className="h-full flex items-center justify-center bg-gray-100 dark:bg-gray-900 rounded-lg">
            <div className="text-center">
              {loading ? (
                <p className="text-xl text-muted-foreground">Loading featured causes...</p>
              ) : (
                <div>
                  <h2 className="text-2xl font-bold mb-4">Welcome to Enaam</h2>
                  <p className="text-muted-foreground mb-6">Supporting causes that make a difference</p>
                  <Link to="/causes">
                    <Button size="lg">Browse All Causes</Button>
                  </Link>
                </div>
              )}
            </div>
          </div>
        )}
      </section>

      {/* Statistics Section */}
      <section className="py-16 bg-gray-50 dark:bg-gray-900">
        <div className="container mx-auto px-4 sm:px-6 lg:px-8">
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 sm:gap-8">
            <Card className="border-0 shadow-md dark:bg-gray-800">
              <CardContent className="pt-6 text-center">
                <div className="bg-rose-100 dark:bg-rose-900/30 w-14 h-14 sm:w-16 sm:h-16 rounded-full flex items-center justify-center mx-auto mb-4 sm:mb-6">
                  <Heart className="w-8 h-8 text-rose-500 dark:text-rose-400" />
                </div>
                <div className="text-3xl sm:text-4xl font-bold mb-2">15,234</div>
                <div className="text-sm text-muted-foreground">Total Donors</div>
              </CardContent>
            </Card>
            <Card className="border-0 shadow-md dark:bg-gray-800">
              <CardContent className="pt-6 text-center">
                <div className="bg-blue-100 dark:bg-blue-900/30 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-6">
                  <Target className="w-8 h-8 text-blue-500 dark:text-blue-400" />
                </div>
                <div className="text-4xl font-bold mb-2">324</div>
                <div className="text-sm text-muted-foreground">Causes Completed</div>
              </CardContent>
            </Card>
            <Card className="border-0 shadow-md dark:bg-gray-800">
              <CardContent className="pt-6 text-center">
                <div className="bg-emerald-100 dark:bg-emerald-900/30 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-6">
                  <Globe2 className="w-8 h-8 text-emerald-500 dark:text-emerald-400" />
                </div>
                <div className="text-4xl font-bold mb-2">45</div>
                <div className="text-sm text-muted-foreground">Countries Reached</div>
              </CardContent>
            </Card>
            <Card className="border-0 shadow-md dark:bg-gray-800">
              <CardContent className="pt-6 text-center">
                <div className="bg-amber-100 dark:bg-amber-900/30 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-6">
                  <Users2 className="w-8 h-8 text-amber-500 dark:text-amber-400" />
                </div>
                <div className="text-4xl font-bold mb-2">1.2M</div>
                <div className="text-sm text-muted-foreground">Lives Impacted</div>
              </CardContent>
            </Card>
          </div>
        </div>
      </section>

      {/* Causes Section */}
      <section className="py-20 bg-white dark:bg-gray-950">
        <div className="container mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex flex-col md:flex-row md:justify-between md:items-end mb-10 md:mb-12">
            <div>
              <h2 className="text-3xl md:text-4xl font-bold mb-2">Current Causes</h2>
              <p className="text-muted-foreground max-w-2xl">Support these ongoing causes and help us make a difference in the world.</p>
            </div>
            <div className="mt-4 md:mt-0">
              <Link to="/causes">
                <Button variant="ghost" className="group">
                  View All Causes
                  <ArrowRight className={`${isRtl ? "mr-2 rotate-180" : "ml-2"} h-4 w-4 transition-transform group-hover:translate-x-1 rtl:group-hover:-translate-x-1`} />
                </Button>
              </Link>
            </div>
          </div>
          
          {initialLoading ? (
            <div className="flex justify-center my-20">
              <div className="animate-spin rounded-full h-16 w-16 border-t-2 border-b-2 border-primary"></div>
            </div>
          ) : (
            <>
              <InfiniteScroll
                next={loadMore}
                hasMore={hasMore}
                isLoading={causesLoading}
                threshold={0.5}
              >
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 md:gap-8">
                  {displayedCauses.map((cause) => (
                    <CauseCard key={cause.id} cause={cause} />
                  ))}
                </div>
              </InfiniteScroll>
              
              {causesLoading && (
                <div className="flex justify-center mt-10">
                  <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-primary"></div>
                </div>
              )}
              
              {!causesLoading && !hasMore && displayedCauses.length > 0 && (
                <p className="text-center text-muted-foreground py-6 mt-4">
                  {t('common.noMoreItems')}
                </p>
              )}
              
              {!causesLoading && displayedCauses.length === 0 && (
                <div className="bg-gray-50 dark:bg-gray-800 rounded-lg p-10 text-center my-8">
                  <h3 className="text-xl font-medium mb-4">No causes found</h3>
                  <p className="text-muted-foreground mb-6">We couldn't find any causes matching your criteria.</p>
                </div>
              )}
            </>
          )}
        </div>
      </section>
      
      {/* Partners Section */}
      {partners.length > 0 && (
        <section className="py-16 bg-gray-50 dark:bg-gray-900">
          <div className="container mx-auto px-4 sm:px-6 lg:px-8">
            <div className="text-center mb-12">
              <h2 className="text-3xl md:text-4xl font-bold mb-4">Our Partners</h2>
              <p className="text-muted-foreground max-w-3xl mx-auto">
                We work with trusted partners to ensure our causes make the greatest impact possible.
              </p>
            </div>
            <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-8 md:gap-12">
              {partners.map((partner) => (
                <div key={partner.id} className="flex flex-col items-center justify-center">
                  <div className="bg-white dark:bg-gray-800 shadow-md p-4 rounded-lg w-full aspect-square flex items-center justify-center">
                    <img 
                      src={partner.logo} 
                      alt={partner.name} 
                      className="max-h-16 md:max-h-20 w-auto transition-transform hover:scale-110"
                    />
                  </div>
                  <div className="mt-4 text-center">
                    <h3 className="font-medium">{partner.name}</h3>
                    {partner.website && (
                      <a 
                        href={partner.website} 
                        target="_blank" 
                        rel="noopener noreferrer" 
                        className="text-sm text-primary hover:underline"
                      >
                        Visit Website
                      </a>
                    )}
                  </div>
                </div>
              ))}
            </div>
          </div>
        </section>
      )}
      
      {/* Our Impact Section */}
      <section className="py-20 bg-white dark:bg-gray-950">
        <div className="container mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-16">
            <h2 className="text-3xl md:text-4xl font-bold mb-4">{t('home.impact.title', 'Our Impact')}</h2>
            <p className="text-muted-foreground max-w-3xl mx-auto">
              {t('home.impact.subtitle', 'Through the generous support of our donors, we have achieved significant milestones in various areas.')}
            </p>
          </div>
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-10">
            <div className="text-center">
              <div className="bg-blue-100 dark:bg-blue-900/20 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-6">
                <Building2 className="w-8 h-8 text-blue-500 dark:text-blue-400" />
              </div>
              <h3 className="text-xl font-semibold mb-3">{t('home.impact.community.title', 'Community Development')}</h3>
              <p className="text-muted-foreground">
                {t('home.impact.community.description', "We've supported 284 community development projects, benefiting over 1.5 million people in need.")}
              </p>
            </div>
            <div className="text-center">
              <div className="bg-emerald-100 dark:bg-emerald-900/20 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-6">
                <Briefcase className="w-8 h-8 text-emerald-500 dark:text-emerald-400" />
              </div>
              <h3 className="text-xl font-semibold mb-3">{t('home.impact.education.title', 'Education Initiatives')}</h3>
              <p className="text-muted-foreground">
                {t('home.impact.education.description', 'Our education programs have helped 42,000 children gain access to quality education and resources.')}
              </p>
            </div>
            <div className="text-center">
              <div className="bg-amber-100 dark:bg-amber-900/20 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-6">
                <BarChart3 className="w-8 h-8 text-amber-500 dark:text-amber-400" />
              </div>
              <h3 className="text-xl font-semibold mb-3">{t('home.impact.economic.title', 'Economic Empowerment')}</h3>
              <p className="text-muted-foreground">
                {t('home.impact.economic.description', "We've funded 450 small businesses and entrepreneurial ventures in underprivileged communities.")}
              </p>
            </div>
            <div className="text-center">
              <div className="bg-rose-100 dark:bg-rose-900/20 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-6">
                <ShieldCheck className="w-8 h-8 text-rose-500 dark:text-rose-400" />
              </div>
              <h3 className="text-xl font-semibold mb-3">{t('home.impact.health.title', 'Health & Wellness')}</h3>
              <p className="text-muted-foreground">
                {t('home.impact.health.description', 'Our medical initiatives have provided healthcare access to 320,000 people in remote and underserved areas.')}
              </p>
            </div>
          </div>
        </div>
      </section>
      
      {/* CTA Section */}
      <section className="py-20 bg-primary text-white">
        <div className="container mx-auto px-4 sm:px-6 lg:px-8">
          <div className="max-w-4xl mx-auto text-center">
            <h2 className="text-3xl md:text-4xl font-bold mb-6">{t('home.cta.title', 'Ready to Make a Difference?')}</h2>
            <p className="text-xl mb-8 text-white/90">
              {t('home.cta.description', 'Join thousands of supporters who are changing lives through their generosity. Every contribution, no matter the size, helps us create positive change.')}
            </p>
            <div className="flex flex-col sm:flex-row gap-4 justify-center">
              <Link to="/causes">
                <Button 
                  size="lg" 
                  variant="secondary" 
                  className="w-full sm:w-auto h-14 px-8 text-lg shadow-lg hover:shadow-xl transition-all duration-300"
                >
                  {t('home.cta.exploreCauses', 'Explore Causes')}
                </Button>
              </Link>
              {!user && (
                <Link to="/signup">
                  <Button 
                    size="lg" 
                    variant="outline" 
                    className="bg-transparent border-white text-white hover:bg-white hover:text-primary w-full sm:w-auto h-14 px-8 text-lg shadow-lg hover:shadow-xl transition-all duration-300"
                  >
                    {t('home.cta.signUp', 'Sign Up Today')}
                  </Button>
                </Link>
              )}
            </div>
          </div>
        </div>
      </section>
    </Layout>
  );
}