import { MainNav } from './MainNav';
import { BackToTop } from './BackToTop';

interface LayoutProps {
  children: React.ReactNode;
}

export function Layout({ children }: LayoutProps) {
  return (
    <div className="min-h-screen bg-gray-50 dark:bg-gray-900 relative">
      <MainNav />
      <main className="relative w-full max-w-[1400px] mx-auto pt-16 px-4 sm:px-6 lg:px-8">
        {children}
      </main>
      <BackToTop />
    </div>
  );
}