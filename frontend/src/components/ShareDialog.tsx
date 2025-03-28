import { useState } from 'react';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Share2, Copy, Check, Facebook, Twitter, Linkedin, Mail } from 'lucide-react';
import { useToast } from '@/components/ui/use-toast';
import { useTranslation } from 'react-i18next';

interface ShareDialogProps {
  causeTitle: string;
  causeId: string;
}

export function ShareDialog({ causeTitle, causeId }: ShareDialogProps) {
  const [copied, setCopied] = useState(false);
  const { toast } = useToast();
  const { t, i18n } = useTranslation();
  const isRtl = i18n.language === 'ar';
  const shareUrl = `${window.location.origin}/causes/${causeId}`;

  const handleCopy = async () => {
    try {
      await navigator.clipboard.writeText(shareUrl);
      setCopied(true);
      setTimeout(() => setCopied(false), 2000);
      toast({
        description: t('share.linkCopied'),
      });
    } catch (err) {
      toast({
        variant: "destructive",
        description: t('share.copyFailed'),
      });
    }
  };

  const shareViaFacebook = () => {
    window.open(
      `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(shareUrl)}`,
      '_blank'
    );
  };

  const shareViaTwitter = () => {
    window.open(
      `https://twitter.com/intent/tweet?url=${encodeURIComponent(shareUrl)}&text=${encodeURIComponent(t('share.supportCause', { causeTitle }))}`,
      '_blank'
    );
  };

  const shareViaLinkedIn = () => {
    window.open(
      `https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(shareUrl)}`,
      '_blank'
    );
  };

  const shareViaEmail = () => {
    window.location.href = `mailto:?subject=${encodeURIComponent(t('share.supportCause', { causeTitle }))}&body=${encodeURIComponent(t('share.checkOutCause', { shareUrl }))}`;
  };

  return (
    <Dialog>
      <DialogTrigger asChild>
        <Button variant="outline" className="w-full" size="lg">
          <Share2 className={isRtl ? "ml-2 h-5 w-5" : "mr-2 h-5 w-5"} /> {t('share.share')}
        </Button>
      </DialogTrigger>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>{t('share.shareCause')}</DialogTitle>
        </DialogHeader>
        <div className="flex items-center space-x-2 mt-4">
          <div className="grid flex-1 gap-2">
            <Input
              value={shareUrl}
              readOnly
              className="h-10 sm:h-12"
            />
          </div>
          <Button 
            type="button" 
            size="icon" 
            variant="outline"
            className="h-10 sm:h-12 w-10 sm:w-12"
            onClick={handleCopy}
          >
            {copied ? <Check className="h-4 w-4" /> : <Copy className="h-4 w-4" />}
          </Button>
        </div>
        <div className="mt-6">
          <h4 className="text-sm font-medium mb-3">{t('share.shareVia')}</h4>
          <div className="grid grid-cols-4 gap-2">
            <Button 
              variant="outline" 
              className="h-12 w-full"
              onClick={shareViaFacebook}
            >
              <Facebook className="h-5 w-5" />
            </Button>
            <Button 
              variant="outline" 
              className="h-12 w-full"
              onClick={shareViaTwitter}
            >
              <Twitter className="h-5 w-5" />
            </Button>
            <Button 
              variant="outline" 
              className="h-12 w-full"
              onClick={shareViaLinkedIn}
            >
              <Linkedin className="h-5 w-5" />
            </Button>
            <Button 
              variant="outline" 
              className="h-12 w-full"
              onClick={shareViaEmail}
            >
              <Mail className="h-5 w-5" />
            </Button>
          </div>
        </div>
      </DialogContent>
    </Dialog>
  );
}