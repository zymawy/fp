"use client"

import * as React from 'react';
import Autoplay from 'embla-carousel-autoplay';
import {
  Carousel,
  CarouselContent,
  CarouselItem,
  CarouselNext,
  CarouselPrevious,
} from '@/components/ui/carousel';

const HeroSection: React.FC = () => {
  const plugin = React.useRef(Autoplay({ delay: 2000, stopOnInteraction: true }));

  const slides = [
    {
      image: '/images/slide1.jpg',
      title: 'Empower Change with Your Contribution',
      description: 'Support meaningful causes and make a difference.',
      link: '/donate',
    },
    {
      image: '/images/slide2.jpg',
      title: 'Together, We Can Make an Impact',
      description: 'Your donations help provide education and resources.',
      link: '/causes',
    },
    // Add more slides as needed
  ];

  return (
    <section className="relative bg-gray-50 py-12">
      <div className="container mx-auto px-6 lg:px-12">
        <Carousel
          plugins={[plugin.current]}
          onMouseEnter={plugin.current.stop}
          onMouseLeave={plugin.current.reset}
        >
          <CarouselContent className="-ml-4">
            {slides.map((slide, index) => (
              <CarouselItem key={index} className="pl-4">
                <div className="relative overflow-hidden rounded-lg shadow-lg">
                  <img
                    src={slide.image}
                    alt={slide.title}
                    className="w-full h-96 object-cover"
                  />
                  <div className="absolute inset-0 bg-gradient-to-t from-black to-transparent opacity-50"></div>
                  <div className="absolute bottom-8 left-8 text-white">
                    <h1 className="text-3xl font-bold mb-2">{slide.title}</h1>
                    <p className="text-lg mb-4">{slide.description}</p>
                    <a
                      href={slide.link}
                      className="inline-block bg-indigo-600 py-2 px-4 rounded text-white font-semibold hover:bg-indigo-700 transition"
                    >
                      Learn More
                    </a>
                  </div>
                </div>
              </CarouselItem>
            ))}
          </CarouselContent>
          <CarouselPrevious />
          <CarouselNext />
        </Carousel>
      </div>
    </section>
  );
};

export default HeroSection;
