import { useState, useEffect } from 'react';
import { Link, useNavigate, useLocation } from 'react-router-dom';
import { Eye, EyeOff, LogIn } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { AuthLayout } from '@/components/AuthLayout';
import { useAuth } from '@/hooks/useAuth';
import { useTranslation } from 'react-i18next';
import { Loader } from 'lucide-react';

export default function SignIn() {
  const [showPassword, setShowPassword] = useState(false);
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);
  const { signIn, user } = useAuth();
  const { t, i18n } = useTranslation();
  const isRtl = i18n.language === 'ar';
  const [isSubmitting, setIsSubmitting] = useState(false);
  const navigate = useNavigate();
  const location = useLocation();
  
  // Get the path the user was trying to access before being redirected to login
  const from = location.state?.from?.pathname || '/';

  // Redirect if user is already logged in
  useEffect(() => {
    if (user) {
      // Navigate to the requested page or default to home
      navigate(from, { replace: true });
    }
  }, [user, navigate, from]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsSubmitting(true);
    try {
      await signIn(email, password);
      // The useEffect above will handle the redirect after successful login
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <AuthLayout>
      <div className="w-full max-w-[650px] animate-fade-in transition-all duration-300">
        <Card className="border-0 shadow-lg dark:bg-gray-800/90 backdrop-blur-sm">
          <form onSubmit={handleSubmit}>
            <CardHeader className="space-y-3">
              <div className="mx-auto w-12 h-12 bg-primary/10 dark:bg-primary/20 rounded-full flex items-center justify-center mb-2">
                <LogIn className="w-6 h-6 text-primary dark:text-primary-foreground" />
              </div>
              <CardTitle className="text-2xl font-bold text-center">{t('auth.signInTitle')}</CardTitle>
              <CardDescription className="text-center text-sm sm:text-base">
                {t('auth.enterCredentials')}
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="space-y-2">
                <Label htmlFor="email" className="text-sm font-medium">{t('auth.email')}</Label>
                <Input 
                  id="email" 
                  type="email" 
                  placeholder={`${t('auth.email')}`}
                  className="h-12 text-base transition-all duration-200 focus:ring-2 focus:ring-primary/20"
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  required
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="password" className="text-sm font-medium">{t('auth.password')}</Label>
                <div className="relative">
                  <Input
                    id="password"
                    type={showPassword ? "text" : "password"}
                    placeholder={`${t('auth.enterYourPassword')}`}
                    className="h-12 text-base pr-10 transition-all duration-200 focus:ring-2 focus:ring-primary/20"
                    value={password}
                    onChange={(e) => setPassword(e.target.value)}
                    required
                  />
                  <button
                    type="button"
                    onClick={() => setShowPassword(!showPassword)}
                    className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 transition-colors"
                    aria-label={showPassword ? t('auth.hidePassword') : t('auth.showPassword')}
                  >
                    {showPassword ? <EyeOff size={18} /> : <Eye size={18} />}
                  </button>
                </div>
              </div>
              <Link 
                to="/forgot-password"
                className="block text-sm text-right text-primary hover:underline transition-colors"
              >
                {t('auth.forgotPassword')}
              </Link>
              <Button 
                className="w-full h-12 text-base transition-all duration-300" 
                type="submit"
                disabled={isSubmitting}
              >
                {isSubmitting ? (
                  <>
                    <Loader className="mr-2 h-4 w-4 animate-spin" />
                    {t('auth.signingIn')}
                  </>
                ) : (
                  <>
                    <LogIn className={isRtl ? "ml-2 h-4 w-4" : "mr-2 h-4 w-4"} /> 
                    {t('auth.signInTitle')}
                  </>
                )}
              </Button>
            </CardContent>
            <CardFooter className="flex flex-col space-y-4 text-center">
              <div className="text-sm text-muted-foreground">
                {t('auth.dontHaveAccount')}{' '}
                <Link to="/signup" className="text-primary hover:underline font-medium transition-colors">
                  {t('auth.signUpTitle')}
                </Link>
              </div>
            </CardFooter>
          </form>
        </Card>
      </div>
    </AuthLayout>
  );
}