import { useState, useEffect } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { Eye, EyeOff, UserPlus, Loader, AlertCircle } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { AuthLayout } from '@/components/AuthLayout';
import { useAuth } from '@/hooks/useAuth';
import { useTranslation } from 'react-i18next';
import { Alert, AlertDescription } from '@/components/ui/alert';

export default function SignUp() {
  const [showPassword, setShowPassword] = useState(false);
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [firstName, setFirstName] = useState('');
  const [lastName, setLastName] = useState('');
  const [error, setError] = useState<string | null>(null);
  const { signUp, user } = useAuth();
  const { t, i18n } = useTranslation();
  const isRtl = i18n.language === 'ar';
  const [isSubmitting, setIsSubmitting] = useState(false);
  const navigate = useNavigate();

  // Redirect if user is already logged in
  useEffect(() => {
    if (user) {
      navigate('/');
    }
  }, [user, navigate]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsSubmitting(true);
    setError(null);
    
    try {
      await signUp(email, password, firstName, lastName);
    } catch (err: any) {
      console.error("Registration error:", err);
      
      // Handle specific error messages based on the error message
      if (err.message && err.message.includes('User already exists')) {
        setError(t('auth.errors.userExists'));
      } else {
        setError(t('auth.errors.genericError'));
      }
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <AuthLayout>
      <div className="w-full max-w-[729px] animate-fade-in transition-all duration-300">
        <Card className="border-0 shadow-lg dark:bg-gray-800/90 backdrop-blur-sm">
          <form onSubmit={handleSubmit}>
            <CardHeader className="space-y-3">
              <div className="mx-auto w-12 h-12 bg-primary/10 dark:bg-primary/20 rounded-full flex items-center justify-center mb-2">
                <UserPlus className="w-6 h-6 text-primary dark:text-primary-foreground" />
              </div>
              <CardTitle className="text-2xl font-bold text-center">{t('auth.signUpTitle')}</CardTitle>
              <CardDescription className="text-center text-sm sm:text-base">
                {t('auth.enterDetails')}
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              {error && (
                <Alert variant="destructive" className="mb-4">
                  <AlertCircle className="h-4 w-4" />
                  <AlertDescription className="ml-2">{error}</AlertDescription>
                </Alert>
              )}
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label htmlFor="firstName" className="text-sm font-medium">{t('auth.firstName')}</Label>
                  <Input 
                    id="firstName" 
                    placeholder={t('auth.firstName')}
                    className="h-12 text-base transition-all duration-200 focus:ring-2 focus:ring-primary/20"
                    value={firstName}
                    onChange={(e) => setFirstName(e.target.value)}
                    required
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="lastName" className="text-sm font-medium">{t('auth.lastName')}</Label>
                  <Input 
                    id="lastName" 
                    placeholder={t('auth.lastName')}
                    className="h-12 text-base transition-all duration-200 focus:ring-2 focus:ring-primary/20"
                    value={lastName}
                    onChange={(e) => setLastName(e.target.value)}
                    required
                  />
                </div>
              </div>
              <div className="space-y-2">
                <Label htmlFor="email" className="text-sm font-medium">{t('auth.email')}</Label>
                <Input 
                  id="email" 
                  type="email" 
                  placeholder={t('auth.email')}
                  className="h-12 text-base transition-all duration-200 focus:ring-2 focus:ring-primary/20"
                  value={email}
                  onChange={(e) => {
                    setEmail(e.target.value);
                    // Clear errors when user types
                    if (error) setError(null);
                  }}
                  required
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="password" className="text-sm font-medium">{t('auth.password')}</Label>
                <div className="relative">
                  <Input
                    id="password"
                    type={showPassword ? "text" : "password"}
                    placeholder={t('auth.createPassword')}
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
                <p className="text-xs text-muted-foreground mt-1">
                  {t('auth.passwordRequirements')}
                </p>
              </div>
              <Button 
                className="w-full h-12 text-base transition-all duration-300" 
                type="submit"
                disabled={isSubmitting}
              >
                {isSubmitting ? (
                  <>
                    <Loader className={`${isRtl ? "ml-2" : "mr-2"} h-4 w-4 animate-spin`} />
                    {t('auth.signingUp')}
                  </>
                ) : (
                  <>
                    <UserPlus className={isRtl ? "ml-2 h-4 w-4" : "mr-2 h-4 w-4"} /> 
                    {t('auth.createAccount')}
                  </>
                )}
              </Button>
            </CardContent>
            <CardFooter className="flex flex-col space-y-4 text-center">
              <div className="text-sm text-muted-foreground">
                {t('auth.alreadyHaveAccount')}{' '}
                <Link to="/signin" className="text-primary hover:underline font-medium transition-colors">
                  {t('auth.signInTitle')}
                </Link>
              </div>
              {error && error === t('auth.errors.userExists') && (
                <Button
                  variant="link"
                  className="p-0 h-auto text-sm text-primary hover:underline"
                  onClick={() => navigate('/signin', { state: { email } })}
                >
                  {t('auth.signInTitle')}
                </Button>
              )}
            </CardFooter>
          </form>
        </Card>
      </div>
    </AuthLayout>
  );
}