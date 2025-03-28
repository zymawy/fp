import { useState, useEffect } from 'react';
import { ArrowLeft, Send, MailQuestion } from 'lucide-react';
import { Link, useNavigate } from 'react-router-dom';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { AuthLayout } from '@/components/AuthLayout';
import { useAuth } from '@/hooks/useAuth';
import { useTranslation } from 'react-i18next';

export default function ForgotPassword() {
  const [email, setEmail] = useState('');
  const [loading, setLoading] = useState(false);
  const { resetPassword, user } = useAuth();
  const { t, i18n } = useTranslation();
  const isRtl = i18n.language === 'ar';
  const navigate = useNavigate();

  // Redirect if user is already logged in
  useEffect(() => {
    if (user) {
      navigate('/');
    }
  }, [user, navigate]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    await resetPassword(email);
    setLoading(false);
  };

  return (
    <AuthLayout>
      <div className="w-full max-w-[650px] animate-fade-in transition-all duration-300">
        <Card className="border-0 shadow-lg dark:bg-gray-800/90 backdrop-blur-sm">
          <form onSubmit={handleSubmit}>
            <CardHeader className="space-y-3">
              <div className="mx-auto w-12 h-12 bg-primary/10 dark:bg-primary/20 rounded-full flex items-center justify-center mb-2">
                <MailQuestion className="w-6 h-6 text-primary dark:text-primary-foreground" />
              </div>
              <CardTitle className="text-2xl font-bold text-center">{t('auth.resetPassword')}</CardTitle>
              <CardDescription className="text-center text-sm sm:text-base">
                {t('auth.resetPasswordInstructions')}
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="space-y-2">
                <Label htmlFor="email" className="text-sm font-medium">{t('auth.email')}</Label>
                <Input 
                  id="email" 
                  type="email" 
                  placeholder={t('auth.email')}
                  className="h-12 text-base transition-all duration-200 focus:ring-2 focus:ring-primary/20"
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  required
                />
              </div>
              <Button 
                className="w-full h-12 text-base transition-all duration-300" 
                type="submit"
                disabled={loading}
              >
                {loading ? (
                  <>
                    <div className={`${isRtl ? "ml-2" : "mr-2"} h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent`}></div>
                    {t('auth.sending')}
                  </>
                ) : (
                  <>
                    <Send className={isRtl ? "ml-2 h-4 w-4" : "mr-2 h-4 w-4"} /> 
                    {t('auth.sendInstructions')}
                  </>
                )}
              </Button>
            </CardContent>
            <CardFooter className="flex flex-col space-y-4 text-center">
              <Link 
                to="/signin" 
                className="text-sm text-primary hover:underline inline-flex items-center font-medium transition-colors"
              >
                <ArrowLeft className={isRtl ? "ml-2 h-4 w-4 rotate-180" : "mr-2 h-4 w-4"} /> {t('auth.backToSignIn')}
              </Link>
            </CardFooter>
          </form>
        </Card>
      </div>
    </AuthLayout>
  );
}