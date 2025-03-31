import * as React from 'react';

interface InfiniteScrollProps {
  isLoading: boolean;
  hasMore: boolean;
  next: () => unknown;
  threshold?: number;
  root?: Element | Document | null;
  rootMargin?: string;
  reverse?: boolean;
  children?: React.ReactNode;
}

export default function InfiniteScroll({
  isLoading,
  hasMore,
  next,
  threshold = 0.5,
  root = null,
  rootMargin = '0px',
  reverse,
  children,
}: InfiniteScrollProps) {
  const observer = React.useRef<IntersectionObserver>();
  const sentinelRef = React.useRef<HTMLDivElement>(null);

  React.useEffect(() => {
    let safeThreshold = threshold;
    if (threshold < 0 || threshold > 1) {
      console.warn(
        'threshold should be between 0 and 1. You are exceeding the range. Will use default value: 0.5',
      );
      safeThreshold = 0.5;
    }

    // Don't observe when loading or when there's no more data
    if (isLoading || !hasMore) return;

    if (observer.current) observer.current.disconnect();
    
    const currentSentinel = sentinelRef.current;
    if (!currentSentinel) return;

    // Create a new IntersectionObserver instance
    observer.current = new IntersectionObserver(
      (entries) => {
        if (entries[0].isIntersecting && hasMore && !isLoading) {
          console.log('Infinite scroll triggered, loading more items...');
          next();
        }
      },
      { threshold: safeThreshold, root, rootMargin },
    );
    
    // Start observing the sentinel element
    observer.current.observe(currentSentinel);

    // Clean up observer on unmount or when dependencies change
    return () => {
      if (observer.current) {
        observer.current.disconnect();
      }
    };
  }, [hasMore, isLoading, next, threshold, root, rootMargin]);

  return (
    <div className="infinite-scroll-container">
      {children}
      <div 
        ref={sentinelRef}
        style={{ height: '10px', margin: '20px 0' }}
        aria-hidden="true"
        className="infinite-scroll-sentinel"
      />
    </div>
  );
} 