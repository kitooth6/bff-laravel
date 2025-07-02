# Laravel 8 Apache統合版 Dockerfile
FROM php:8.2-apache

# システムパッケージのインストール
RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    default-mysql-client \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libxml2-dev \
    libonig-dev \
    libzip-dev \
    && rm -rf /var/lib/apt/lists/*

# PHP拡張機能のインストール
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
    pdo_mysql \
    mbstring \
    zip \
    gd \
    xml \
    bcmath \
    pcntl \
    exif

# Composerのインストール
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Apacheモジュールの有効化
RUN a2enmod rewrite

# 作業ディレクトリの設定
WORKDIR /var/www/html

# .dockerignoreファイルを活用してファイルコピー
COPY . .

# Composer依存関係のインストール(開発用)
RUN composer install --optimize-autoloader

# 権限の設定
# より確実な権限設定
RUN chown -R www-data:www-data /var/www/html \
    && find /var/www/html -type d -exec chmod 755 {} \; \
    && find /var/www/html -type f -exec chmod 644 {} \; \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

# HTTPポート（80）を公開
EXPOSE 80

# Apache起動
CMD ["apache2-foreground"]
