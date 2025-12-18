FROM php:8.2-apache

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Enable Apache modules
RUN a2enmod rewrite headers

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libsodium-dev \
    && docker-php-ext-install sodium \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html/

# Install PHP dependencies
RUN if [ -f composer.json ]; then composer install --no-dev --optimize-autoloader; fi

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/config \
    && chmod -R 775 /var/www/html/content

# Configure PHP
RUN echo "session.cookie_httponly = 1" >> /usr/local/etc/php/conf.d/security.ini \
    && echo "session.cookie_samesite = Strict" >> /usr/local/etc/php/conf.d/security.ini \
    && echo "session.use_strict_mode = 1" >> /usr/local/etc/php/conf.d/security.ini \
    && echo "expose_php = Off" >> /usr/local/etc/php/conf.d/security.ini

EXPOSE 80

CMD ["apache2-foreground"]
