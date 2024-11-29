"use client"
import React, { createContext, useContext, useState, useEffect } from "react";
import { route as ziggyRoute } from "ziggy-js";
import axios from "@/lib/axios";

interface ZiggyContextProps {
  ziggyRoutes: any;
  isReady: boolean;
  generateRoute: (name: string, params?: {}, absolute?: boolean) => string;
}

const ZiggyContext = createContext<ZiggyContextProps | undefined>(undefined);

export const ZiggyProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const [ziggyRoutes, setZiggyRoutes] = useState<any>(null);
  const [isReady, setIsReady] = useState(false);

  useEffect(() => {
    const fetchRoutes = async () => {
      try {
        const { data } = await axios.get("routes");
        console.log(data)
        setZiggyRoutes(data);
        setIsReady(true);
      } catch (error) {
        console.error("Failed to fetch Ziggy routes:", error);
      }
    };

    fetchRoutes();
  }, []);

  const generateRoute = (name: string, params = {}, absolute = true) =>
    ziggyRoute(name, params, absolute, ziggyRoutes);

  return (
    <ZiggyContext.Provider value={{ ziggyRoutes, isReady, generateRoute }}>
      {children}
    </ZiggyContext.Provider>
  );
};

export const useZiggy = () => {
  const context = useContext(ZiggyContext);
  if (!context) {
    throw new Error("useZiggy must be used within a ZiggyProvider");
  }
  return context;
};
