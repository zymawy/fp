import { useState, useEffect } from 'react';
import { Layout } from '@/components/Layout';
import { CauseCard } from '@/components/CauseCard';
import { useCauses } from '@/hooks/useCauses';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Slider } from '@/components/ui/slider';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { X, Filter, ChevronDown, ChevronUp } from 'lucide-react';
import { api } from '@/lib/api';
import InfiniteScroll from '@/components/ui/infinite-scroll';
import { InfiniteScrollSentinel } from '@/components/ui/infinite-scroll-sentinel';
import { useTranslation } from 'react-i18next';

export default function Causes() {
  const { causes, hasMore, loading, initialLoading, loadMore, updateFilters } = useCauses();
  const [categoryOptions, setCategoryOptions] = useState<{ id: string; name: string; }[]>([]);
  const [showFilters, setShowFilters] = useState(false);
  const [activeFilters, setActiveFilters] = useState({
    categoryId: 'all',
    minAmount: '',
    maxAmount: '',
    urgencyLevel: 'any',
    location: '',
    search: ''
  });
  const { t, i18n } = useTranslation();
  const isRtl = i18n.language === 'ar';

  // Function to get translated category name if available
  const getTranslatedCategoryName = (category: { id: string; name: string }) => {
    // Try to get translation using category.id as key
    const translationKey = `categories.${category.id}`;
    const translated = t(translationKey);
    
    // If the translation exists and is not the same as the key (fallback behavior of i18n)
    if (translated !== translationKey) {
      return translated;
    }
    
    // Fallback to the original name
    return category.name;
  };

  // Load categories
  useEffect(() => {
    const fetchCategories = async () => {
      try {
        const categories = await api.categories.list();
        setCategoryOptions(categories);
      } catch (error) {
        console.error("Failed to load categories:", error);
      }
    };
    
    fetchCategories();
  }, []);

  // Apply filters
  const applyFilters = () => {
    // Convert values and remove empty or 'all'/'any' filter values
    const filtersToApply = Object.fromEntries(
      Object.entries(activeFilters)
        .filter(([key, value]) => {
          return value !== '' && value !== 'all' && value !== 'any';
        })
    );
    
    updateFilters(filtersToApply);
    setShowFilters(false);
  };

  // Clear filters
  const clearFilters = () => {
    setActiveFilters({
      categoryId: 'all',
      minAmount: '',
      maxAmount: '',
      urgencyLevel: 'any',
      location: '',
      search: ''
    });
    updateFilters({});
    setShowFilters(false);
  };

  return (
    <Layout>
      <section className="py-12">
        <div className="container px-4 mx-auto">
          <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-8">
            <div>
              <h1 className="text-3xl sm:text-4xl font-bold mb-2">{t('nav.causes')}</h1>
              <p className="text-muted-foreground">{t('causes.filter')}</p>
            </div>
            
            {/* Search and Filter */}
            <div className="w-full md:w-auto flex flex-col sm:flex-row gap-3 mt-4 md:mt-0">
              <div className="relative w-full sm:w-64">
                <Input
                  type="text"
                  placeholder={t('common.search') + '...'}
                  value={activeFilters.search}
                  onChange={(e) => setActiveFilters(prev => ({ ...prev, search: e.target.value }))}
                  className={isRtl ? "pl-10" : "pr-10"}
                  onKeyDown={(e) => e.key === 'Enter' && applyFilters()}
                />
                {activeFilters.search && (
                  <button 
                    onClick={() => {
                      setActiveFilters(prev => ({ ...prev, search: '' }));
                      updateFilters({ ...activeFilters, search: '' });
                    }}
                    className={`absolute ${isRtl ? "left-3" : "right-3"} top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600`}
                  >
                    <X className="h-4 w-4 text-foreground" />
                  </button>
                )}
              </div>
              
              <Button 
                variant="outline" 
                className="flex items-center gap-2"
                onClick={() => setShowFilters(!showFilters)}
              >
                {isRtl ? (
                  <>
                    {showFilters ? <ChevronUp className="h-4 w-4 text-foreground" /> : <ChevronDown className="h-4 w-4 text-foreground" />}
                    {t('causes.filter')}
                    <Filter className="h-4 w-4 text-foreground" />
                  </>
                ) : (
                  <>
                    <Filter className="h-4 w-4 text-foreground" />
                    {t('causes.filter')}
                    {showFilters ? <ChevronUp className="h-4 w-4 text-foreground" /> : <ChevronDown className="h-4 w-4 text-foreground" />}
                  </>
                )}
              </Button>
            </div>
          </div>
          
          {/* Filter Panel */}
          {showFilters && (
            <div className="bg-card border rounded-lg p-5 mb-8 shadow-md">
              <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                  <Label className="mb-2 block">{t('causes.categories')}</Label>
                  <Select 
                    value={activeFilters.categoryId}
                    onValueChange={(value) => setActiveFilters(prev => ({ ...prev, categoryId: value }))}
                  >
                    <SelectTrigger>
                      <SelectValue placeholder={t('causes.all')} />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="all">{t('causes.all')}</SelectItem>
                      {categoryOptions.map(category => (
                        <SelectItem key={category.id} value={category.id}>
                          {getTranslatedCategoryName(category)}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>
                
                <div>
                  <Label className="mb-2 block">{t('causes.urgency')}</Label>
                  <Select 
                    value={activeFilters.urgencyLevel}
                    onValueChange={(value) => setActiveFilters(prev => ({ ...prev, urgencyLevel: value }))}
                  >
                    <SelectTrigger>
                      <SelectValue placeholder={t('causes.any')} />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="any">{t('causes.any')}</SelectItem>
                      <SelectItem value="low">{t('causes.low')}</SelectItem>
                      <SelectItem value="medium">{t('causes.medium')}</SelectItem>
                      <SelectItem value="high">{t('causes.high')}</SelectItem>
                      <SelectItem value="critical">{t('causes.critical')}</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                
                <div>
                  <Label className="mb-2 block">{t('cause.location')}</Label>
                  <Input 
                    placeholder={t('causes.any')}
                    value={activeFilters.location}
                    onChange={(e) => setActiveFilters(prev => ({ ...prev, location: e.target.value }))}
                  />
                </div>
                
                <div className="md:col-span-2">
                  <Label className="mb-2 block">{t('cause.goalAmount')}</Label>
                  <div className="flex gap-4 items-center">
                    <Input
                      type="number"
                      placeholder={t('common.min')}
                      min="0"
                      value={activeFilters.minAmount}
                      onChange={(e) => setActiveFilters(prev => ({ ...prev, minAmount: e.target.value }))}
                    />
                    <span>{t('causes.to')}</span>
                    <Input
                      type="number"
                      placeholder={t('common.max')}
                      min="0"
                      value={activeFilters.maxAmount}
                      onChange={(e) => setActiveFilters(prev => ({ ...prev, maxAmount: e.target.value }))}
                    />
                  </div>
                </div>
                
                <div className="flex items-end gap-3">
                  <Button onClick={applyFilters} className="flex-1">{t('causes.applyFilters')}</Button>
                  <Button variant="outline" onClick={clearFilters}>{t('causes.clearFilters')}</Button>
                </div>
              </div>
            </div>
          )}
          
          {/* Filter Tags */}
          <div className="flex flex-wrap gap-2 mb-6">
            {Object.entries(activeFilters).map(([key, value]) => 
              value && value !== 'all' && value !== 'any' ? (
                <div 
                  key={key}
                  className="bg-primary/10 text-primary rounded-full px-3 py-1 text-sm flex items-center gap-1"
                >
                  <span>{key === 'categoryId' 
                    ? `${t('causes.categories')}: ${categoryOptions.find(c => c.id === value) 
                        ? getTranslatedCategoryName(categoryOptions.find(c => c.id === value)!) 
                        : value}`
                    : key === 'minAmount'
                    ? `${t('common.min')}: ${isRtl ? `${value}${t('common.currency')}` : `${t('common.currency')}${value}`}`
                    : key === 'maxAmount'
                    ? `${t('common.max')}: ${isRtl ? `${value}${t('common.currency')}` : `${t('common.currency')}${value}`}`
                    : key === 'urgencyLevel'
                    ? `${t('causes.urgency')}: ${value === 'low' ? t('causes.low') : 
                                             value === 'medium' ? t('causes.medium') : 
                                             value === 'high' ? t('causes.high') : 
                                             value === 'critical' ? t('causes.critical') : value}`
                    : key === 'search'
                    ? `${t('common.search')}: ${value}`
                    : key === 'location'
                    ? `${t('cause.location')}: ${value}`
                    : `${key}: ${value}`
                  }</span>
                  <button 
                    onClick={() => {
                      setActiveFilters(prev => ({ ...prev, [key]: '' }));
                      const newFilters = { ...activeFilters };
                      newFilters[key as keyof typeof activeFilters] = '';
                      updateFilters(newFilters);
                    }}
                    className="text-primary hover:text-primary/80"
                    aria-label={t('causes.clearFilter')}
                  >
                    <X className="h-3 w-3 text-primary" />
                  </button>
                </div>
              ) : null
            )}
          </div>

          {/* Causes Grid with Infinite Scroll */}
          {initialLoading ? (
            <div className="flex justify-center my-20">
              <div className="animate-spin rounded-full h-16 w-16 border-t-2 border-b-2 border-primary"></div>
            </div>
          ) : causes.length === 0 && !loading ? (
            <div className="text-center py-12">
              <p className="text-xl text-muted-foreground">{t('causes.noCausesFound')}</p>
              <p className="mt-2 text-muted-foreground">{t('causes.tryAdjustingFilters')}</p>
              <Button onClick={clearFilters} variant="outline" className="mt-4">{t('causes.clearFilters')}</Button>
            </div>
          ) : (
            <>
              <InfiniteScroll
                hasMore={hasMore}
                next={loadMore}
                threshold={0.5}
                isLoading={loading}
              >
                <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                  {causes.map((cause) => (
                    <CauseCard key={cause.id} cause={cause} isRtl={isRtl} />
                  ))}
                </div>
              </InfiniteScroll>
              
              {loading && (
                <div className="flex justify-center mt-10">
                  <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-primary"></div>
                </div>
              )}
              
              {!loading && !hasMore && causes.length > 0 && (
                <p className="text-center text-muted-foreground py-6 mt-4">
                  {t('common.noMoreItems')}
                </p>
              )}
            </>
          )}
        </div>
      </section>
    </Layout>
  );
}