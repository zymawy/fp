# Vite React Frontend

This is a Vite-based React application. The Docker setup provides both development and production environments.

## Development

For local development with hot-reloading:

```bash
# Start the development environment
docker-compose up ui

# Access the application at http://localhost:5173
```

This will mount your local frontend directory into the container, allowing for live code updates.

## Production

For production builds:

```bash
# Build and start the production environment
docker-compose -f docker-compose.prod.yml up -d ui

# Access the application at http://localhost:80 (or configured port)
```

The production build is optimized and served using Nginx.

## Building Without Docker

If you prefer to run the application without Docker:

```bash
# Install dependencies
npm install

# Start development server
npm run dev

# Build for production
npm run build

# Preview production build
npm run preview
```

## Available Scripts

- `npm run dev` - Start the development server
- `npm run build` - Build for production
- `npm run preview` - Preview the production build
- `npm run lint` - Run ESLint
- `npm run postinstall` - Generate Prisma client
- `npm run seed` - Seed the database 