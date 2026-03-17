# 1. Usamos una imagen de PHP con Apache
FROM php:8.2-apache

# 2. Instalamos dependencias del sistema y extensiones de PHP
# Sinceramente, aquí añadimos libjpeg y libfreetype para que Dompdf vea tus fotos
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

# 3. Instalamos Composer desde la imagen oficial
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 4. Configuramos el directorio de trabajo
WORKDIR /var/www/html

# 5. Copiamos los archivos de tu proyecto
COPY . .

# 6. Ejecutamos Composer para descargar la carpeta 'vendor'
RUN composer install --no-interaction --optimize-autoloader --no-dev

# 7. Ajustamos permisos para Apache
RUN chown -R www-data:www-data /var/www/html

# 8. Ajuste de puerto para Google Cloud Run (8080)
RUN sed -i 's/80/8080/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

# 9. Exponemos el puerto y arrancamos
EXPOSE 8080
CMD ["apache2-foreground"]