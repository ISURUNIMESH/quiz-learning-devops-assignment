# Use official PHP + Apache image
FROM php:8.1-apache

# Copy all project files to Apache web root
COPY . /var/www/html/

# Give proper permissions
RUN chown -R www-data:www-data /var/www/html

# Expose port 80
EXPOSE 80