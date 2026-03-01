FROM php:8.2-apache

# Install PHP extensions first (better layer caching)
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Create non-root user
RUN useradd -m appuser

WORKDIR /var/www/html

# Copy only necessary files (assuming .dockerignore exists)
COPY . .

# Change ownership
RUN chown -R appuser:appuser /var/www/html

USER appuser

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=3s \
  CMD curl -f http://localhost/ || exit 1
