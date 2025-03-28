import React, { useEffect, ReactNode } from 'react';
import { useTranslation } from 'react-i18next';
import { setDirection } from '@/i18n';

interface TranslationProviderProps {
  children: ReactNode;
}

/**
 * TranslationProvider component to wrap the application with translation context
 * and handle RTL/LTR direction switching when language changes
 */
const TranslationProvider: React.FC<TranslationProviderProps> = ({ children }) => {
  const { i18n } = useTranslation();
  
  // Handle RTL/LTR direction on language change
  useEffect(() => {
    setDirection(i18n.language);
    
    // Listen for language changes
    const handleLanguageChange = (lng: string) => {
      setDirection(lng);
    };
    
    i18n.on('languageChanged', handleLanguageChange);
    
    return () => {
      i18n.off('languageChanged', handleLanguageChange);
    };
  }, [i18n]);
  
  return <>{children}</>;
};

export default TranslationProvider; 