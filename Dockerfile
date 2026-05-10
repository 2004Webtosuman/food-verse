# Use the official PHP 8.2 Apache image
FROM php:8.2-apache

# Install PDO MySQL extension
RUN docker-php-ext-install pdo pdo_mysql

# Enable Apache mod_rewrite (needed for many PHP apps)
RUN a2enmod rewrite

# Copy your project files to the container
COPY . /var/www/html/

# Set permissions for Apache
RUN chown -R www-data:www-data /var/www/html

# Expose port 80
EXPOSE 80
