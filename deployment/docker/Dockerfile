# =============================================================================
# Multi-Stage Dockerfile for Full-Stack Laravel (PHP + Node.js/Vite)
# =============================================================================
# Multi-Stage Dockerfile for Laravel + Inertia + Vite (Full-Stack)
# Based on production best practices from Laravel community
# Uses serversideup/php for pre-built PHP extensions (NO COMPILATION!)
# =============================================================================

# -----------------------------------------------------------------------------
# Stage 1: Frontend Builder (Node.js + Vite)
# -----------------------------------------------------------------------------
FROM node:20-alpine AS frontend-builder

WORKDIR /app

# Install minimal PHP and Composer for vendor dependencies
# Only mbstring and openssl are required for Composer to work
# Using --ignore-platform-reqs for other dependencies since we're just fetching packages
RUN apk add --no-cache curl php83 php83-phar php83-mbstring php83-openssl \
    && ln -s /usr/bin/php83 /usr/bin/php \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copy composer files and install PHP vendor dependencies
# Use --ignore-platform-reqs for extensions we don't need (dom, gd, etc.)
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --prefer-dist --no-interaction --ignore-platform-reqs

# Copy package files for npm dependencies
COPY package*.json ./

# Install all npm dependencies (including devDependencies for build)
RUN npm ci --production=false

# Copy vendor/ and source files for Vite compilation
COPY resources/ resources/
COPY vendor/ vendor/
COPY vite.config.js ./
COPY tailwind.config.js* postcss.config.js* jsconfig.json* ./
COPY public/ public/

# Build frontend assets with Vite
RUN npm run build

# Verify build output exists
RUN if [ ! -d "public/build" ]; then \
      echo "ERROR: Frontend build failed - public/build directory not found"; \
      exit 1; \
    fi && \
    if [ ! -f "public/build/manifest.json" ]; then \
      echo "ERROR: Frontend build failed - manifest.json not found"; \
      exit 1; \
    fi && \
    echo "✅ Frontend assets compiled successfully" && \
    ls -lah public/build/

# -----------------------------------------------------------------------------
# Stage 2: PHP/Laravel Application (Pre-built Laravel-optimized image)
# -----------------------------------------------------------------------------
FROM serversideup/php:8.3-fpm-nginx-alpine AS base

# Set labels
LABEL maintainer="Anohou"

# Arguments
ARG WWWUSER=www-data
ARG WWWGROUP=www-data

# Set working directory
WORKDIR /var/www/html

# Environment variables
ENV TZ=UTC
ENV APP_ENV=production

# Switch to root to install system packages
USER root

# Install additional packages
# Add supervisor back since we'll use artisan serve instead of nginx
RUN apk add --no-cache \
    bash \
    supervisor \
    python3 \
    py3-yaml \
    msmtp \
    ca-certificates

# Environment variables for supervisor
ENV SUPERVISOR_PHP_COMMAND="/usr/local/bin/php -d variables_order=EGPCS /var/www/html/artisan serve --host=0.0.0.0 --port=9000"
ENV SUPERVISOR_PHP_USER="www-data"

# Copy application code
COPY --chown=$WWWUSER:$WWWGROUP .  /var/www/html

# Copy compiled frontend assets from builder stage
COPY --from=frontend-builder --chown=$WWWUSER:$WWWGROUP /app/public/build /var/www/html/public/build

# Install Composer dependencies with production optimizations
# Use --ignore-platform-reqs since some extensions may not be in the build env
# but will be available at runtime (the image has them pre-compiled)
RUN composer install \
    --no-interaction \
    --no-dev \
    --no-scripts \
    --prefer-dist \
    --optimize-autoloader \
    --classmap-authoritative \
    --ignore-platform-reqs

# Remove any cached config/routes that may reference dev dependencies
# This prevents "Class not found" errors for packages like laravel/pail
RUN rm -rf bootstrap/cache/*.php 2>/dev/null || true

# Set proper permissions (create dirs first since they may be excluded by .dockerignore)
RUN mkdir -p /var/www/html/storage /var/www/html/bootstrap/cache && \
    chown -R $WWWUSER:$WWWGROUP /var/www/html && \
    chmod -R 775 /var/www/html/storage && \
    chmod -R 775 /var/www/html/bootstrap/cache

# Create supervisor configuration
COPY deployment/docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Copy startup script
COPY deployment/docker/scripts/startup.sh /usr/local/bin/startup.sh
COPY deployment/docker/scripts/generate-env.sh /usr/local/bin/generate-env.sh
RUN chmod +x /usr/local/bin/startup.sh /usr/local/bin/generate-env.sh

# Ensure deployment templates directory exists
RUN chown -R $WWWUSER:$WWWGROUP /var/www/html/deployment/templates

# Create supervisor log directory (needs root, before switching to www-data)
RUN mkdir -p /var/log/supervisor && chown -R $WWWUSER:$WWWGROUP /var/log/supervisor

# Switch back to non-root user
USER $WWWUSER

# Expose port
EXPOSE 8000

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=40s --retries=3 \
    CMD php artisan --version || exit 1

# Start application
ENTRYPOINT ["/usr/local/bin/startup.sh"]
