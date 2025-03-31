import React, { useState, useEffect } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Heart, Users, ExternalLink, ArrowRight, ImageOff } from 'lucide-react';
import { EnhancedProgressBar } from './EnhancedProgressBar';
import { DonationProgressLive } from './DonationProgressLive';
import { useTranslation } from 'react-i18next';
import { Cause } from '@/hooks/useCauses';

// Default image for causes (data URL for a gray placeholder with text)
const DEFAULT_CAUSE_IMAGE = 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="800" height="400" viewBox="0 0 800 400"><rect width="800" height="400" fill="%23f0f0f0"/><text x="400" y="200" font-family="Arial" font-size="32" text-anchor="middle" fill="%23aaaaaa">Image not available</text></svg>';

interface CauseCardProps {
  cause: Cause;
  isRtl?: boolean;
}

export function CauseCard({ cause, isRtl = false }: CauseCardProps) {
  const { t, i18n } = useTranslation();
  const [imageError, setImageError] = useState(false);
  const navigate = useNavigate();
  
  // Function to handle donation button click
  const handleDonateClick = () => {
    navigate(`/causes/${cause.id}`);
  };
  // Get category name with fallbacks
  const getCategoryName = () => {
    // First check for category_name from API
    if (cause.category_name && cause.category_name.toLowerCase() !== 'other') {
      return cause.category_name;
    }

    // Then check nested category object
    if (cause.category && cause.category.name && cause.category.name.toLowerCase() !== 'other') {
      return cause.category.name;
    }

    // For numeric categoryId, try to map to translated category name
    const categoryId = cause.categoryId || cause.category_id;
    if (categoryId && /^\d+$/.test(categoryId)) {
      switch(categoryId) {
        case '1': return t('categories.education');
        case '2': return t('categories.health');
        case '3': return t('categories.emergency');
        case '4': return t('categories.food');
        case '5': return t('categories.water');
        case '6': return t('categories.shelter');
      }
    }

    // If we reach here, this is truly an uncategorized cause
    // Return empty string to hide the category badge if truly uncategorized
    return '';
  };
  
  const categoryName = getCategoryName();
  
  // Ensure we have valid values to prevent NaN
  const safeRaisedAmount = typeof cause.raisedAmount === 'number' && !isNaN(cause.raisedAmount) 
    ? cause.raisedAmount 
    : typeof cause.raised_amount === 'number' && !isNaN(cause.raised_amount)
      ? cause.raised_amount
      : 0;

  const safeGoalAmount = typeof cause.goalAmount === 'number' && !isNaN(cause.goalAmount) && cause.goalAmount > 0
    ? cause.goalAmount 
    : typeof cause.goal_amount === 'number' && !isNaN(cause.goal_amount) && cause.goal_amount > 0
      ? cause.goal_amount
      : typeof cause.target_amount === 'number' && !isNaN(cause.target_amount) && cause.target_amount > 0
        ? cause.target_amount
        : 1; // Default to 1 to prevent division by zero

  // Get donor count
  const safeDonorCount = (() => {
    if (typeof cause.donorCount === 'number' && !isNaN(cause.donorCount)) {
      return cause.donorCount;
    }
    
    if (typeof cause.donor_count === 'number' && !isNaN(cause.donor_count)) {
      return cause.donor_count;
    }
    
    if (typeof cause.donors_count === 'number' && !isNaN(cause.donors_count)) {
      return cause.donors_count;
    }
    
    if (typeof cause.unique_donors === 'number' && !isNaN(cause.unique_donors)) {
      return cause.unique_donors;
    }
    
    return 0;
  })();

  // Calculate percentage safely
  const percentageRaised = (() => {
    // If raised amount is 0, percentage is always 0 regardless of goal
    if (safeRaisedAmount === 0) {
      return 0;
    }

    // Use progress_percentage if available and valid
    if (typeof cause.progress_percentage === 'number' && !isNaN(cause.progress_percentage)) {
      const boundedProgress = Math.min(Math.max(cause.progress_percentage, 0), 100);
      return boundedProgress;
    }
    
    // Calculate based on raised/goal amounts
    const calculated = Math.min(Math.round((safeRaisedAmount / safeGoalAmount) * 100), 100);
    return calculated;
  })();

  // Safe progress percentage for DonationProgressLive
  const safeProgressPercentage = percentageRaised;

  // Safe target and raised amount
  const safeTargetAmount = typeof cause.target_amount === 'number' && !isNaN(cause.target_amount)
    ? cause.target_amount
    : safeGoalAmount;

  const safeRaisedAmountApi = typeof cause.raised_amount === 'number' && !isNaN(cause.raised_amount)
    ? cause.raised_amount
    : safeRaisedAmount;

  // Get image URL
  const imageUrl = cause.imageUrl || cause.featured_image || cause.image_url || cause.image || DEFAULT_CAUSE_IMAGE;

  // Handle image error by setting a default image
  const handleImageError = (e: React.SyntheticEvent<HTMLImageElement, Event>) => {
    setImageError(true);
    e.currentTarget.src = DEFAULT_CAUSE_IMAGE;
  };

  // Reset image error if imageUrl changes
  useEffect(() => {
    setImageError(false);
  }, [imageUrl]);

  return (
    <Card className="overflow-hidden hover:shadow-lg transition-shadow border-0 shadow-md dark:bg-gray-800">
      <div className="relative">
        {imageError ? (
          <div className="w-full h-48 bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
            <ImageOff className="h-10 w-10 text-gray-400" />
          </div>
        ) : (
          <img 
            src={imageUrl} 
            alt={cause.title || t('causes.untitled')}
            className="w-full h-48 object-cover"
            onError={handleImageError}
          />
        )}
        {categoryName && (
          <div className="absolute top-3 left-3">
            <span className="text-xs font-medium px-3 py-1 bg-primary/10 dark:bg-primary/20 text-primary dark:text-primary-foreground rounded-full backdrop-blur-sm whitespace-nowrap">
              {categoryName}
            </span>
          </div>
        )}
      </div>
      <CardHeader>
        <CardTitle className="line-clamp-1">{cause.title || t('causes.untitled')}</CardTitle>
        <CardDescription className="line-clamp-2">{cause.description || t('causes.noDescription')}</CardDescription>
      </CardHeader>
      <CardContent>
        <div className="space-y-4">
          <DonationProgressLive 
            causeId={cause.id}
            initialProgress={safeProgressPercentage}
            initialRaisedAmount={safeRaisedAmountApi}
            targetAmount={safeTargetAmount}
            className="mb-4"
            currencySymbol="$"
            showConnectionStatus={false}
          />
          
          <div className="flex items-center justify-between text-sm">
            <div className="flex items-center gap-1">
              <Users className="h-4 w-4 text-muted-foreground" />
              <span className="text-muted-foreground">{safeDonorCount} {t('causes.donors')}</span>
            </div>
            <div className="font-medium text-primary">
              {percentageRaised || safeRaisedAmount}% {t('causes.funded')}
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