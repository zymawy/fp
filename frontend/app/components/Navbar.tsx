"use client"

import Link from 'next/link';
import { cn } from '@/lib/utils';
import { useRouter } from 'next/navigation'

const Navbar: React.FC = () => {

  const router = useRouter()


  return (
    <nav className="bg-white shadow-md py-4">
      <div className="container mx-auto flex justify-between items-center px-4 lg:px-8">
        <Link href="/" className="text-2xl font-extrabold text-indigo-600">
          YourLogo
        </Link>
        <div className="space-x-6 hidden md:flex">
          <NavItem href="/" label="Home" />
          <NavItem href="/causes" label="Causes" />
          <NavItem href="/about" label="About Us" />
          <NavItem href="/contact" label="Contact" />
          <NavItem href="/profile" label="Profile" />
        </div>
        <button className="md:hidden text-gray-700">Menu</button>
      </div>
    </nav>
  );
};

interface NavItemProps {
  href: string;
  label: string;
}

const NavItem: React.FC<NavItemProps> = ({ href, label }) => (
  <Link
    href={href}
    className={cn(
      'text-gray-700 hover:text-indigo-600 transition font-medium'
    )}
  >
    {label}
  </Link>
);

export default Navbar;
