import { Progress } from '@/components/ui/progress';
import { useEffect, useState } from 'react';

interface EnhancedProgressBarProps {
  value: number;
  max: number;
  showPercentage?: boolean;
  showValues?: boolean;
  className?: string;
  showAnimation?: boolean;
}

export function EnhancedProgressBar({
  value,
  max,
  showPercentage = true,
  showValues = true,
  className = '',
  showAnimation = true
}: EnhancedProgressBarProps) {
  const [progress, setProgress] = useState(0);
  const percentage = Math.min(Math.round((value / max) * 100), 100);
  
  useEffect(() => {
    if (showAnimation) {
      const timer = setTimeout(() => {
        setProgress(percentage);
      }, 200);
      return () => clearTimeout(timer);
    } else {
      setProgress(percentage);
    }
  }, [percentage, showAnimation]);

  return (
    <div className={`space-y-2 ${className}`}>
      <Progress value={progress} className="h-2.5" />
      
      {(showPercentage || showValues) && (
        <div className="flex justify-between items-center text-sm">
          {showPercentage && (
            <span className="font-medium text-primary">
              {percentage}% Complete
            </span>
          )}
          
          {showValues && (
            <span className="text-muted-foreground">
              ${value.toLocaleString()} of ${max.toLocaleString()}
            </span>
          )}
        </div>
      )}
    </div>
  );
} 