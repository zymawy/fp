"use client"
import React, { useEffect, useState } from "react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { useZiggy } from "@/context/ZiggyContext";
import Link from "next/link";




const LoginPage: React.FC = () => {

  // const { generateRoute, isReady } = useZiggy();

  useEffect(() => {

    // const fetchRoutes = async () => {
    //   const response = await fetch("https://api.enaam.orb.local/ziggy");
    //   const json = await response.json();
    //   setRoutes(json);
    // };
    // fetchRoutes();
    // const registerUrl = route("register", {}, false, routes); // Laravel register route

    // console.log(route('register'))
    
  }, []);

  const { generateRoute, isReady } = useZiggy();

  if (!isReady) return <div>Loading...</div>;

  const loginUrl = generateRoute("login");



  // if (true) {
  //   return <div>Loading...</div>;
  // }
// 
  const registerUrl = generateRoute("login"); // Laravel register route

  console.log(registerUrl)

  return (
    <div className="flex justify-center items-center h-screen">
      <Card className="w-full max-w-lg shadow-lg">
        <CardHeader>
          <CardTitle className="text-center text-3xl font-bold text-gray-800">
            Welcome Back
          </CardTitle>
          <p className="text-center text-gray-500 mt-2">
            Login to your account to continue.
          </p>
        </CardHeader>
        <CardContent>
          <form action={generateRoute("login")} method="POST" className="space-y-6 mt-6">
            <div>
              <label htmlFor="email" className="block text-sm font-medium text-gray-700">
                Email Address
              </label>
              <Input id="email" name="email" type="email" placeholder="Enter your email" className="mt-1" />
            </div>
            <div>
              <label htmlFor="password" className="block text-sm font-medium text-gray-700">
                Password
              </label>
              <Input id="password" name="password" type="password" placeholder="Enter your password" className="mt-1" />
            </div>
            <Button type="submit" className="w-full py-3 text-lg font-semibold">
              Login
            </Button>
          </form>
          <div className="mt-6 text-center">
            <p className="text-sm text-gray-600">
              Don’t have an account?{" "}
              <Link href='/auth/register' className="text-indigo-600 font-semibold hover:underline">
                Register
              </Link>
            </p>
          </div>
        </CardContent>
      </Card>
    </div>
  );
};

export default LoginPage;
