FROM php:8.2-apache

# Enable Apache rewrite
RUN a2enmod rewrite

# Install PHP extensions (adjust if needed)
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Set Apache DocumentRoot to ROOT
ENV APACHE_DOCUMENT_ROOT=/var/www/html

# Update Apache config to use ROOT as DocumentRoot
RUN sed -ri -e 's!/var/www/html!/var/www/html!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!/var/www/html!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Copy project files
COPY . /var/www/html

# Permissions
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html

EXPOSE 80
