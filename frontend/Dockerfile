# syntax=docker.io/docker/dockerfile:1

FROM node:18-alpine AS base

# Install dependencies only when needed
FROM base AS deps
# Add build dependencies for native modules
RUN apk add --no-cache libc6-compat python3 make g++ git
WORKDIR /app
RUN apk add --no-cache openssl

# Install dependencies based on the preferred package manager
COPY package.json package-lock.json ./
# Install dependencies
RUN npm install
# Explicitly reinstall esbuild with the exact version to fix version mismatch
RUN npm uninstall esbuild && npm install esbuild@0.25.0

# Development image for live reloading
FROM base AS dev
WORKDIR /app

COPY --from=deps /app/node_modules ./node_modules
COPY . .
EXPOSE 5173
CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0"]

# Rebuild the source code only when needed
FROM base AS builder
WORKDIR /app
COPY --from=deps /app/node_modules ./node_modules
COPY . .

# Build the Vite app
RUN npm run build

# Production image, copy all the files and run nginx
FROM nginx:alpine AS runner
WORKDIR /usr/share/nginx/html

# Remove default nginx static assets
RUN rm -rf ./*

# Copy static assets from builder stage
COPY --from=builder /app/dist .

# Copy nginx configuration
COPY nginx.conf /etc/nginx/conf.d/default.conf

# Expose port
EXPOSE 80

# Start Nginx server
CMD ["nginx", "-g", "daemon off;"]