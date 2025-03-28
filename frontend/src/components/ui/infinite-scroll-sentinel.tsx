import * as React from 'react';
import { cn } from '@/lib/utils';
import { useTranslation } from 'react-i18next';

interface InfiniteScrollSentinelProps
  extends React.HTMLAttributes<HTMLDivElement> {
  isLoading?: boolean;
  hasMore?: boolean;
  loadingText?: string;
  endingText?: string;
}

const InfiniteScrollSentinel = React.forwardRef<
  HTMLDivElement,
  InfiniteScrollSentinelProps
>(
  (
    {
      className,
      isLoading = false,
      hasMore = true,
      loadingText,
      endingText,
      ...props
    },
    ref
  ) => {
    const { t } = useTranslation();
    
    return (
      <div
        ref={ref}
        className={className}
        {...props}
      >
        {isLoading && <p className="text-center text-muted-foreground py-4">{loadingText || t('common.loadingMore')}</p>}
        {!hasMore && !isLoading && <p className="text-center text-muted-foreground py-4">{endingText || t('common.noMoreItems')}</p>}
      </div>
    );
  }
);

InfiniteScrollSentinel.displayName = "InfiniteScrollSentinel";

export { InfiniteScrollSentinel }; 