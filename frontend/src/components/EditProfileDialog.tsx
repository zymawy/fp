import { useState, useEffect } from 'react';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { AlertCircle, Edit, Upload, X } from 'lucide-react';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useProfile } from '@/hooks/useProfile';
import { Alert, AlertDescription } from '@/components/ui/alert';
import PhoneInput from 'react-phone-number-input';
import 'react-phone-number-input/style.css';
import { useTranslation } from 'react-i18next';
import { api } from '@/lib/api';
import { dispatchAuthStateChangeEvent } from '@/hooks/useAuth';

interface FormData {
  first_name: string;
  last_name: string;
  avatar_url: string;
  phone_number: string;
  email: string;
}

const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB
const ALLOWED_FILE_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

export function EditProfileDialog() {
  const { t, i18n } = useTranslation();
  const isRtl = i18n.language === 'ar';
  const [isOpen, setIsOpen] = useState(false);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [previewImage, setPreviewImage] = useState<string | null>(null);
  const { profile, updateProfile } = useProfile();
  const [formData, setFormData] = useState<FormData>(() => ({
    first_name: '',
    last_name: '',
    avatar_url: '',
    phone_number: '',
    email: '',
  }));

  // Update form data when profile changes
  useEffect(() => {
    if (profile) {
      setFormData({
        first_name: profile.first_name || '',
        last_name: profile.last_name || '',
        avatar_url: profile.avatar_url || '',
        phone_number: profile.phone_number || '',
        email: profile.email || '',
      });
    }
  }, [profile]);

  const handleImageChange = async (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (file) {
      setError(null);
      console.log('File selected:', file.name, file.type, file.size);

      if (file.size > MAX_FILE_SIZE) {
        setError(t('profile.avatar.sizeError'));
        return;
      }

      if (!ALLOWED_FILE_TYPES.includes(file.type)) {
        setError(t('profile.avatar.typeError'));
        return;
      }

      try {
        // Create a preview
        const reader = new FileReader();
        reader.onloadend = () => {
          setPreviewImage(reader.result as string);
          console.log('Preview image created');
        };
        reader.readAsDataURL(file);

        console.log('Uploading file to server...');
        // Use API wrapper to upload avatar
        const response = await api.profile.uploadAvatar(file);
        console.log('Upload response:', response);
        
        if (response && response.success && response.url) {
          console.log('Upload successful, new avatar URL:', response.url);
          setFormData(prev => ({ ...prev, avatar_url: response.url }));
          
          // Update localStorage session data with the new avatar URL
          try {
            const storedUser = localStorage.getItem('session');
            if (storedUser) {
              const userData = JSON.parse(storedUser);
              // Update the avatar_url in the user data
              userData.avatar_url = response.url;
              
              // Save updated user data back to localStorage
              localStorage.setItem('session', JSON.stringify(userData));
              
              // Notify the app about the auth state change
              dispatchAuthStateChangeEvent();
              
              console.log('Updated avatar_url in localStorage:', userData);
            }
          } catch (storageError) {
            console.error('Error updating localStorage:', storageError);
            // Continue even if localStorage update fails
          }
        } else {
          console.error('Upload response format invalid:', response);
          throw new Error(response?.message || t('profile.avatar.uploadError'));
        }
      } catch (error) {
        console.error('Image upload error:', error);
        if (error instanceof Error) {
          setError(error.message || t('profile.avatar.uploadError'));
        } else {
          setError(t('profile.avatar.uploadError'));
        }
      }
    }
  };

  const validatePhoneNumber = (phone: string) => {
    const phoneRegex = /^\+?[1-9]\d{1,14}$/;
    return phone === '' || phoneRegex.test(phone);
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setError(null);

    if (!validatePhoneNumber(formData.phone_number)) {
      setError(t('profile.phoneNumberInvalid'));
      setLoading(false);
      return;
    }

    try {
      const success = await updateProfile({
        first_name: formData.first_name,
        last_name: formData.last_name,
        avatar_url: formData.avatar_url,
        phone_number: formData.phone_number,
      });

      if (success) {
        setIsOpen(false);
      }
    } catch (error) {
      setError(t('profile.updateError'));
      console.error(error);
    } finally {
      setLoading(false);
    }
  };

  const handleClose = () => {
    setIsOpen(false);
    setError(null);
    setPreviewImage(null);
    if (profile) {
      setFormData({
        first_name: profile.first_name || '',
        last_name: profile.last_name || '',
        avatar_url: profile.avatar_url || '',
        phone_number: profile.phone_number || '',
        email: profile.email || '',
      });
    }
  };

  return (
    <Dialog open={isOpen} onOpenChange={setIsOpen}>
      <DialogTrigger asChild>
        <Button variant="outline" size="sm" className="w-full">
          <Edit className={isRtl ? "h-4 w-4 ml-2" : "h-4 w-4 mr-2"} /> {t('profile.editProfile')}
        </Button>
      </DialogTrigger>
      <DialogContent className="sm:max-w-[425px]">
        <DialogHeader>
          <DialogTitle>{t('profile.editProfile')}</DialogTitle>
        </DialogHeader>
        <form onSubmit={handleSubmit} className="space-y-6">
          {error && (
            <Alert variant="destructive">
              <AlertCircle className="h-4 w-4" />
              <AlertDescription>{error}</AlertDescription>
            </Alert>
          )}

          {/* Profile Image */}
          <div className="flex flex-col items-center gap-4">
            <Avatar className="h-24 w-24 relative group bg-primary/10">
              <AvatarImage src={previewImage || formData.avatar_url || undefined} />
              <AvatarFallback>
                {formData.first_name?.[0]}{formData.last_name?.[0]}
              </AvatarFallback>
              <div className="absolute inset-0 bg-black/60 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity rounded-full">
                <label 
                  htmlFor="avatar-upload" 
                  className="cursor-pointer text-white flex items-center gap-2"
                >
                  <Upload className="h-4 w-4" />
                </label>
              </div>
            </Avatar>
            {previewImage && (
              <Button
                type="button"
                variant="outline"
                size="sm"
                onClick={() => setPreviewImage(null)}
                className="h-8"
              >
                <X className={isRtl ? "h-4 w-4 ml-2" : "h-4 w-4 mr-2"} /> {t('profile.avatar.remove')}
              </Button>
            )}
            <input
              id="avatar-upload"
              type="file"
              accept="image/*"
              onChange={handleImageChange}
              className="hidden"
            />
            <p className="text-xs text-muted-foreground">
              {t('profile.avatar.supportedFormats')}
            </p>
          </div>

          {/* Form Fields */}
          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label htmlFor="firstName">{t('profile.firstName')}</Label>
              <Input
                id="firstName"
                value={formData.first_name}
                onChange={(e) => setFormData(prev => ({ ...prev, first_name: e.target.value }))}
                required
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="lastName">{t('profile.lastName')}</Label>
              <Input
                id="lastName"
                value={formData.last_name}
                onChange={(e) => setFormData(prev => ({ ...prev, last_name: e.target.value }))}
                required
              />
            </div>
          </div>

          <div className="space-y-2">
            <Label htmlFor="email">{t('auth.email')}</Label>
            <Input
              id="email"
              type="email"
              value={formData.email}
              disabled
              className="bg-muted"
            />
            <p className="text-xs text-muted-foreground">
              {t('profile.emailChangeNote')}
            </p>
          </div>

          <div className="space-y-2">
            <Label htmlFor="phone">{t('profile.phoneNumber')}</Label>
            <PhoneInput
              international
              countryCallingCodeEditable={false}
              defaultCountry="US"
              value={formData.phone_number}
              onChange={(value) => setFormData(prev => ({ ...prev, phone_number: value || '' }))}
              placeholder={t('auth.phoneNumber')}
            />
            <p className="text-xs text-muted-foreground">
              {t('profile.phoneNumberHint')}
            </p>
          </div>

          <div className="flex justify-end gap-2">
            <Button type="button" variant="outline" onClick={handleClose}>
              {t('common.cancel')}
            </Button>
            <Button type="submit" disabled={loading}>
              {loading ? t('common.loading') : t('profile.update')}
            </Button>
          </div>
        </form>
      </DialogContent>
    </Dialog>
  );
}