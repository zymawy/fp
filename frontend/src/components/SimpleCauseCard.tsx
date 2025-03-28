import { Link, useNavigate } from 'react-router-dom';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Users, Heart } from 'lucide-react';
import { EnhancedProgressBar } from './EnhancedProgressBar';
import { useTranslation } from 'react-i18next';

// Simplified cause type that matches our mock data
interface SimpleCause {
  id: string;
  title: string;
  description: string;
  imageUrl: string;
  raisedAmount: number;
  goalAmount: number;
  donorCount: number;
  categoryId: string;
  status: string;
  urgencyLevel: string;
  location: string | null;
  startDate: Date;
  endDate: Date;
  createdAt: Date;
  updatedAt: Date;
  [key: string]: any;
}

interface SimpleCauseCardProps {
  cause: SimpleCause;
}

export function SimpleCauseCard({ cause }: SimpleCauseCardProps) {
  const { t, i18n } = useTranslation();
  const isRtl = i18n.language === 'ar';
  const navigate = useNavigate();

  // Helper function to get category name
  const getCategoryName = (categoryId: string) => {
    // Try to get translation using category.id as key
    const translationKey = `categories.${categoryId}`;
    const translated = t(translationKey);
    
    // If the translation exists and is not the same as the key (fallback behavior of i18n)
    if (translated !== translationKey) {
      return translated;
    }
    
    // Fallback to the hardcoded values
    switch(categoryId) {
      case '1': return t('categories.education');
      case '2': return t('categories.health');
      case '3': return t('categories.emergency');
      default: return t('categories.other');
    }
  };
  
  // Function to handle donation button click
  const handleDonateClick = () => {
    console.log(`Navigating to cause detail page for: ${cause.id}`);
    navigate(`/causes/${cause.id}`);
  };
  
  // Get category name
  const categoryName = getCategoryName(cause.categoryId);
  
  // Calculate percentage
  const percentageRaised = Math.round((cause.raisedAmount / cause.goalAmount) * 100);
  
  return (
    <Card className="overflow-hidden hover:shadow-lg transition-shadow border-0 shadow-md dark:bg-gray-800">
      <div className="relative">
        <img 
          src={cause.imageUrl} 
          alt={cause.title}
          className="w-full h-48 object-cover"
        />
        <div className="absolute top-3 left-3">
          <span className="text-xs font-medium px-3 py-1 bg-primary/10 dark:bg-primary/20 text-primary dark:text-primary-foreground rounded-full backdrop-blur-sm">
            {categoryName}
          </span>
        </div>
      </div>
      <CardHeader>
        <CardTitle className="line-clamp-1">{cause.title}</CardTitle>
        <CardDescription className="line-clamp-2">{cause.description}</CardDescription>
      </CardHeader>
      <CardContent>
        <div className="space-y-4">
          <EnhancedProgressBar 
            value={cause.raisedAmount}
            max={cause.goalAmount}
            showPercentage={false}
          />
          
          <div className="flex items-center justify-between text-sm">
            <div className="flex items-center gap-1">
              <Users className="h-4 w-4 text-muted-foreground" />
              <span className="text-muted-foreground">{cause.donorCount} {t('cause.donors')}</span>
            </div>
            <div className="font-medium text-primary">
              {percentageRaised}% {t('causes.funded')}
            </div>
          </div>
        </div>
      </CardContent>
      <CardFooter>
        <Button 
          className="w-full" 
          variant="default"
          onClick={handleDonateClick}
        >
          {t('causes.viewCause')}
          <Heart className={`${isRtl ? "mr-2" : "ml-2"} h-4 w-4`} />
        </Button>
      </CardFooter>
    </Card>
  );
} 