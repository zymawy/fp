import i18n from 'i18next';
import { initReactI18next } from 'react-i18next';
import LanguageDetector from 'i18next-browser-languagedetector';

// Import translations
import enTranslation from './locales/en.json';
import arTranslation from './locales/ar.json';

// Configuration
i18n
  // Detect user language
  .use(LanguageDetector)
  // Pass the i18n instance to react-i18next
  .use(initReactI18next)
  // Initialize i18next
  .init({
    resources: {
      en: {
        translation: enTranslation
      },
      ar: {
        translation: arTranslation
      }
    },
    fallbackLng: 'en',
    debug: import.meta.env.DEV,
    
    interpolation: {
      escapeValue: false // React already escapes values
    },

    // Enable RTL for Arabic
    returnObjects: true,
    detection: {
      order: ['localStorage', 'navigator'],
      lookupLocalStorage: 'language',
      caches: ['localStorage'],
    }
  });

// Helper function to set RTL direction based on language
export const setDirection = (language: string) => {
  const dir = language === 'ar' ? 'rtl' : 'ltr';
  document.documentElement.dir = dir;
  document.documentElement.lang = language;
  
  // Add RTL class to body for styling purposes
  if (dir === 'rtl') {
    document.body.classList.add('rtl');
  } else {
    document.body.classList.remove('rtl');
  }
  
  return dir;
};

// Set initial direction based on detected language
setDirection(i18n.language);

// Listen for language changes
i18n.on('languageChanged', (lng) => {
  setDirection(lng);
});

export default i18n; 