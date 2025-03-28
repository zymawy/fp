import { Link } from 'react-router-dom';
import { Button } from '@/components/ui/button';
import { UserNav } from './UserNav';
import { useAuth } from '@/hooks/useAuth';
import { ThemeToggle } from './ThemeToggle';
import LanguageSwitcher from './LanguageSwitcher';
import { useTranslation } from 'react-i18next';

export function MainNav() {
  const { user, loading } = useAuth();
  const { t } = useTranslation();

  return (
    <header className="sticky top-0 left-0 right-0 bg-white/80 dark:bg-gray-900/90 backdrop-blur-md z-50 border-b dark:border-gray-800">
      <div className="max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
        <Link to="/" className="text-2xl font-bold text-primary">{t('app.name')}</Link>
        <nav className="flex items-center gap-4 sm:gap-8">
          <Link to="/causes" className="text-sm font-medium hover:text-primary transition-colors">
            {t('nav.causes')}
          </Link>
          <ThemeToggle />
          <LanguageSwitcher />
          {loading ? (
            <div className="h-8 w-8 rounded-full bg-muted animate-pulse" />
          ) : user ? (
            <UserNav />
          ) : (
            <>
              <Link to="/signin" className="hidden sm:block">
                <Button variant="outline" size="sm">{t('nav.signin')}</Button>
              </Link>
              <Link to="/signup">
                <Button size="sm">{t('nav.signup')}</Button>
              </Link>
            </>
          )}
        </nav>
      </div>
    </header>
  );
}