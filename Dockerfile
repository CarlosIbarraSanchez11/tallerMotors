# 1. Usamos una imagen de PHP con Apache (versión estable)
FROM php:8.2-apache

# 2. Instalamos las extensiones de MySQL que tu Conexion.php necesita
# Sinceramente, sin esto el sistema no sabrá cómo hablar con la base de datos.
RUN docker-php-ext-install mysqli pdo pdo_mysql && docker-php-ext-enable mysqli

# 3. Instalamos herramientas necesarias (zip para Composer y libpng para PDFs si usas imágenes)
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# 4. Instalamos Composer desde la imagen oficial
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 5. Configuramos el directorio de trabajo
WORKDIR /var/www/html

# 6. Copiamos los archivos de tu proyecto al contenedor
COPY . .

# 7. Ejecutamos Composer para descargar la carpeta 'vendor' (PDFs, Mailer, etc.)
# Sinceramente, como no la subiste a GitHub, Docker la descargará aquí.
RUN composer install --no-interaction --optimize-autoloader --no-dev

# 8. Ajustamos permisos para que Apache pueda leer tus archivos
RUN chown -R www-data:www-data /var/www/html

# 9. Google Cloud Run usa el puerto 8080 por defecto
# Sinceramente, este pequeño truco cambia el puerto de Apache del 80 al 8080.
RUN sed -i 's/80/8080/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

# 10. Exponemos el puerto y arrancamos Apache
EXPOSE 8080
CMD ["apache2-foreground"]