FROM php:8.2-apache

# 1. Instala dependências pesadas (Java, LibreOffice, PDFtk)
# Necessário para: mikehaertl/php-pdftk e phpoffice/phpword
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    libicu-dev \
    default-jre \
    libreoffice-writer \
    libreoffice-java-common \
    pdftk-java \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# 2. Instala extensões PHP
RUN docker-php-ext-install pdo pdo_pgsql gd zip intl

# 3. Ativa Rewrite do Apache
RUN a2enmod rewrite

# 4. Configura Raiz e Permissões do Apache
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf

# 5. Permite .htaccess (Crucial para Laravel)
RUN echo '<Directory /var/www/html/public>' >> /etc/apache2/apache2.conf && \
    echo '    Options Indexes FollowSymLinks' >> /etc/apache2/apache2.conf && \
    echo '    AllowOverride All' >> /etc/apache2/apache2.conf && \
    echo '    Require all granted' >> /etc/apache2/apache2.conf && \
    echo '</Directory>' >> /etc/apache2/apache2.conf

# 6. Finalização
WORKDIR /var/www/html
RUN chown -R www-data:www-data /var/www/html
