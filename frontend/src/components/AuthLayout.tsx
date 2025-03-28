import { Link } from 'react-router-dom';
import { Button } from '@/components/ui/button';
import { ThemeToggle } from './ThemeToggle';
import { useAuth } from '@/hooks/useAuth';
import { UserNav } from './UserNav';
import { useTranslation } from 'react-i18next';
import LanguageSwitcher from './LanguageSwitcher';

interface AuthLayoutProps {
  children: React.ReactNode;
}

export function AuthLayout({ children }: AuthLayoutProps) {
  const { user, loading } = useAuth();
  const { t } = useTranslation();

  return (
    <div className="min-h-screen w-full bg-gradient-to-b from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-950 flex flex-col">
      {/* Header */}
      <header className="fixed top-0 left-0 w-full bg-white/80 dark:bg-gray-900/80 backdrop-blur-md z-50 border-b dark:border-gray-800">
        <div className="max-w-[1400px] mx-auto px-4 h-16 flex items-center justify-between">
          <Link to="/" className="text-2xl font-bold text-primary dark:text-white">{t('app.name')}</Link>
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
                <Link to="/signup" className="hidden sm:block">
                  <Button size="sm">{t('nav.signup')}</Button>
                </Link>
              </>
            )}
          </nav>
        </div>
      </header>

      {/* Main Content */}
      <main className="flex-1 flex items-center justify-center px-4 pt-16 relative">
        {/* Background Pattern */}
        <div className="absolute inset-0 overflow-hidden pointer-events-none">
          <div className="absolute top-0 right-0 w-1/2 h-1/2 bg-primary/[0.03] dark:bg-primary/[0.02] rounded-full blur-3xl transform translate-x-1/3 -translate-y-1/3"></div>
          <div className="absolute bottom-0 left-0 w-1/2 h-1/2 bg-primary/[0.03] dark:bg-primary/[0.02] rounded-full blur-3xl transform -translate-x-1/3 translate-y-1/3"></div>
        </div>
        
        {/* Content */}
        <div className="relative z-10">
          {children}
        </div>
      </main>
    </div>
  );
}