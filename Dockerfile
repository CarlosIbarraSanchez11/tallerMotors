# 1. Usamos la imagen de PHP con Apache
FROM php:8.2-apache

# 2. Instalamos TODO en un solo paso (Ingredientes + Cocina)
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd mysqli pdo pdo_mysql \
    && rm -rf /var/lib/apt/lists/*

# 3. Instalamos Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 4. Directorio de trabajo
WORKDIR /var/www/html

# 5. Copiamos los archivos
COPY . .

# 6. Instalamos dependencias de PHP (Dompdf, Google Storage, etc.)
RUN composer install --no-interaction --optimize-autoloader --no-dev

# 7. Permisos
RUN chown -R www-data:www-data /var/www/html

# 8. Puerto 8080 para Cloud Run
RUN sed -i 's/80/8080/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

EXPOSE 8080
CMD ["apache2-foreground"]