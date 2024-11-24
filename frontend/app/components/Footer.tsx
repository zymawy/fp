import React from 'react';

const Footer: React.FC = () => {
  return (
    <footer className="bg-gray-800 text-gray-300 py-8">
      <div className="container mx-auto text-center">
        <FooterText text="&copy; 2024 Your Non-Profit Organization. All rights reserved." />
        <FooterLinks />
      </div>
    </footer>
  );
};

const FooterText: React.FC<{ text: string }> = ({ text }) => (
  <p className="text-sm">{text}</p>
);

const FooterLinks: React.FC = () => (
  <div className="mt-4 space-x-6">
    <FooterLink href="/" label="Privacy Policy" />
    <FooterLink href="/" label="Terms of Service" />
    <FooterLink href="/" label="Contact Us" />
  </div>
);

const FooterLink: React.FC<{ href: string; label: string }> = ({ href, label }) => (
  <a href={href} className="hover:text-white transition">
    {label}
  </a>
);

export default Footer;
