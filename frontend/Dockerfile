# Use Node.js for building the application
FROM node:20-alpine AS build

WORKDIR /app

RUN npm install -g pnpm


# Copy package files and install dependencies
COPY package*.json ./
RUN pnpm install

# Copy the rest of your application
COPY . .

# Build the application
RUN pnpm build

# # Use NGINX to serve the application
# FROM nginx:alpine

# COPY --from=build /app/build /usr/share/nginx/html

# # Copy NGINX configuration if needed
# COPY ./nginx.conf /etc/nginx/conf.d/default.conf

EXPOSE 80
# CMD ["nginx", "-g", "daemon off;"]
CMD npm run dev

