# Frontend Laravel BFF + Vue.js用 Dockerfile
  FROM php:8.2-apache

  # Node.js 18のインストール
  RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
      && apt-get install -y nodejs

  # PHP拡張とシステムパッケージ
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

  # Composerインストール
  COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

  # Apache設定
  RUN a2enmod rewrite

  # Apache DocumentRoot設定（Laravel用）
  ENV APACHE_DOCUMENT_ROOT /var/www/html/public
  RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
  
  # 作業ディレクトリの設定
  WORKDIR /var/www/html
  COPY . .

  # 依存関係インストール
  RUN composer install --optimize-autoloader
  RUN npm install
  
  # Vue.js用の追加パッケージをインストール
  RUN npm install vue-loader@^15.9.8 --save-dev --legacy-peer-deps

  # Node.jsモジュールの実行権限を修正
  RUN find /var/www/html/node_modules/.bin -type f -exec chmod +x {} \; \
      && find /var/www/html/node_modules -name "*.js" -path "*/bin/*" -exec chmod +x {} \;

  # 権限設定
  RUN chown -R www-data:www-data /var/www/html \
      && find /var/www/html -type d -exec chmod 755 {} \; \
      && find /var/www/html -type f -exec chmod 644 {} \; \
      && chmod -R 775 /var/www/html/storage \
      && chmod -R 775 /var/www/html/bootstrap/cache \
      && chmod -R 755 /var/www/html/node_modules/.bin

  # 開発環境用アセットビルド
  RUN npm run development

  EXPOSE 80
  CMD ["apache2-foreground"]