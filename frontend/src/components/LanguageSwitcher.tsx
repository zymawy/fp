import React from 'react';
import { useTranslation } from 'react-i18next';
import { Button } from "@/components/ui/button";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { Globe } from 'lucide-react';

const LanguageSwitcher: React.FC = () => {
  const { t, i18n } = useTranslation();

  const changeLanguage = (lng: string) => {
    i18n.changeLanguage(lng);
  };

  // Determine if current language is RTL
  const isRtl = i18n.language === 'ar';

  return (
    <DropdownMenu>
      <DropdownMenuTrigger asChild>
        <Button variant="ghost" size="icon" className="relative">
          <Globe className="h-5 w-5 text-foreground" />
          <span className="sr-only">Switch language</span>
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end" className={isRtl ? 'rtl-menu' : ''}>
        <DropdownMenuItem 
          className={i18n.language === 'en' ? 'font-bold' : ''}
          onClick={() => changeLanguage('en')}
        >
          {t('language.en')}
        </DropdownMenuItem>
        <DropdownMenuItem 
          className={i18n.language === 'ar' ? 'font-bold' : ''}
          onClick={() => changeLanguage('ar')}
        >
          {t('language.ar')}
        </DropdownMenuItem>
      </DropdownMenuContent>
    </DropdownMenu>
  );
};

export default LanguageSwitcher; 