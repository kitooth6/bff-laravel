# Laravel BFF + Vue.js 開発環境構築 完全ガイド

## 📋 目次

1. [プロジェクト概要](#プロジェクト概要)
2. [アーキテクチャ設計](#アーキテクチャ設計)
3. [環境構築手順](#環境構築手順)
4. [トラブルシューティング](#トラブルシューティング)
5. [動作確認方法](#動作確認方法)

## プロジェクト概要

### 技術スタック
- **Frontend**: Vue.js 2 + Vue Router + Vuex + Laravel Mix
- **BFF (Backend for Frontend)**: Laravel 8
- **Backend API**: Laravel 8
- **Database**: MySQL 8.0
- **Infrastructure**: Docker + Docker Compose

### 最終的なアーキテクチャ
```
Browser → Vue.js → Laravel BFF (Port 3000) → Laravel API (Port 8080) → MySQL (Port 3306)
```

## アーキテクチャ設計

### BFF (Backend for Frontend) パターンの採用理由
1. **フロントエンド最適化**: UIに特化したAPI設計
2. **セキュリティ**: 直接的なAPIアクセスの回避
3. **柔軟性**: フロントエンドとバックエンドの独立開発
4. **集約**: 複数のバックエンドAPIを統合

### コンテナ構成
- `app_db`: MySQL データベース
- `app_backend`: Laravel API サーバー
- `app_frontend`: Laravel BFF + Vue.js SPA

## 環境構築手順

### 1. プロジェクト初期化

```bash
# プロジェクトディレクトリ作成
mkdir bff-laravel && cd bff-laravel

# 基本ディレクトリ構造作成
mkdir -p docker/mysql docker/apache
```

### 2. MySQL データベース構築

#### 2.1 docker-compose.yml 基本設定

```yaml
version: '3.8'

services:
  # MySQL データベースサーバー
  db:
    image: mysql:8.0
    container_name: app_db
    environment:
      MYSQL_ROOT_PASSWORD: rootpass
      MYSQL_DATABASE: laravel_db
      MYSQL_USER: laravel_user
      MYSQL_PASSWORD: laravel_pass
      TZ: 'Asia/Tokyo'
    ports:
      - "3306:3306"
    volumes:
      - mysql_data:/var/lib/mysql
      - ./docker/mysql/my.cnf:/etc/mysql/conf.d/my.cnf
    networks:
      - app-network

volumes:
  mysql_data:

networks:
  app-network:
    driver: bridge
```

#### 2.2 MySQL 日本語設定

```bash
# MySQLコンテナからmy.cnfをコピー
docker cp app_db:/etc/mysql/my.cnf ./docker/mysql/my.cnf
```

`docker/mysql/my.cnf` に日本語設定を追加：

```ini
[mysqld]
character-set-server=utf8mb4
collation-server=utf8mb4_unicode_ci

[client]
default-character-set=utf8mb4

[mysqldump]
default-character-set=utf8mb4
```

#### 2.3 動作確認

```bash
# コンテナ起動
docker-compose up db

# 接続テスト
docker exec -it app_db mysql -u root -p

# 日本語設定確認
SHOW VARIABLES LIKE 'character_set%';
SHOW VARIABLES LIKE 'collation%';
```

### 3. Laravel Backend API 構築

#### 3.1 Laravel プロジェクト作成

```bash
# backendディレクトリでLaravelプロジェクト作成
docker run --rm -v $(pwd):/app composer create-project laravel/laravel backend "^8.0"
```

#### 3.2 Dockerfile 作成

`backend/Dockerfile`:

```dockerfile
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

# Apache DocumentRoot設定（Laravel用）
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf

# Composerのインストール
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Apacheモジュールの有効化
RUN a2enmod rewrite

# 作業ディレクトリの設定
WORKDIR /var/www/html
COPY . .

# Composer依存関係のインストール(開発用)
RUN composer install --optimize-autoloader

# 権限設定
RUN chown -R www-data:www-data /var/www/html \
    && find /var/www/html -type d -exec chmod 755 {} \; \
    && find /var/www/html -type f -exec chmod 644 {} \; \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

# HTTPポート（80）を公開
EXPOSE 80

# Apache起動
CMD ["apache2-foreground"]
```

#### 3.3 Apache 設定ファイル

`docker/apache/000-default.conf`:

```apache
<VirtualHost *:80>
    DocumentRoot /var/www/html/public
    
    <Directory /var/www/html/public>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
        DirectoryIndex index.php
    </Directory>
</VirtualHost>
```

#### 3.4 docker-compose.yml に追加

```yaml
  # Laravel Backend API サーバー
  backend:
    build:
      context: ./backend
      dockerfile: Dockerfile
    container_name: app_backend
    volumes:
      - ./backend:/var/www/html
      - ./docker/apache/000-default.conf:/etc/apache2/sites-available/000-default.conf
    ports:
      - "8080:80"
    depends_on:
      - db
    networks:
      - app-network
```

#### 3.5 Laravel 設定

`backend/.env`:

```env
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=laravel_db
DB_USERNAME=laravel_user
DB_PASSWORD=laravel_pass
```

### 4. Laravel Frontend BFF + Vue.js 構築

#### 4.1 Laravel プロジェクト作成

```bash
# frontendディレクトリでLaravelプロジェクト作成
docker run --rm -v $(pwd):/app composer create-project laravel/laravel frontend "^8.0"
```

#### 4.2 Dockerfile 作成 (PHP + Node.js)

`frontend/Dockerfile`:

```dockerfile
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
```

#### 4.3 Vue.js パッケージインストール

```bash
# frontendディレクトリで実行
docker run --rm -v $(pwd):/app -w /app node:18-alpine npm install vue@^2.6.14 vue-router@^3.5.4 vuex@^3.6.2 vue-template-compiler --save-dev
```

#### 4.4 webpack.mix.js 設定

```javascript
const mix = require('laravel-mix');

mix.js('resources/js/app.js', 'public/js')
   .vue({ version: 2 })
   .postCss('resources/css/app.css', 'public/css', [
       //
   ]);
```

#### 4.5 Vue.js 基本設定

`frontend/resources/js/app.js`:

```javascript
require("./bootstrap");

import Vue from "vue";
import VueRouter from "vue-router";
import Vuex from "vuex";

// Vue プラグインを使用
Vue.use(VueRouter);
Vue.use(Vuex);

// ルーター設定
const routes = [
    { path: "/", component: () => import("./components/Home.vue") },
    { path: "/about", component: () => import("./components/About.vue") },
];

const router = new VueRouter({
    mode: "history",
    routes,
});

// Vuex ストア
const store = new Vuex.Store({
    state: {
        user: null
    },
    mutations: {
        setUser(state, user) {
            state.user = user;
        }
    }
});

// Vue インスタンス
const app = new Vue({
    el: "#app",
    router,
    store,
});
```

#### 4.6 SPA対応設定

`frontend/routes/web.php`:

```php
<?php

use Illuminate\Support\Facades\Route;

// SPA用のルート設定（Vue Routerの履歴モード対応）
Route::get('/{any}', function () {
    return view('welcome');
})->where('any', '.*');
```

#### 4.7 Blade テンプレート修正

`frontend/resources/views/welcome.blade.php`:

```html
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Laravel BFF + Vue.js</title>

        <!-- Styles -->
        <link href="{{ mix('css/app.css') }}" rel="stylesheet">
    </head>
    <body>
        <div id="app">
            <router-view></router-view>
        </div>

        <!-- Scripts -->
        <script src="{{ mix('js/app.js') }}"></script>
    </body>
</html>
```

#### 4.8 Vue コンポーネント作成

`frontend/resources/js/components/Home.vue`:

```vue
<template>
  <div class="home">
    <h1>Home Page</h1>
    <p>Welcome to Laravel BFF + Vue.js!</p>
    <p>User: {{ user ? user.name : 'Guest' }}</p>
    <button @click="updateUser">Update User</button>
    <router-link to="/about">Go to About</router-link>
  </div>
</template>

<script>
export default {
  name: 'Home',
  computed: {
    user() {
      return this.$store.state.user;
    }
  },
  methods: {
    updateUser() {
      this.$store.commit('setUser', { name: 'John Doe', id: 1 });
    }
  }
}
</script>

<style scoped>
.home {
  padding: 20px;
}
button {
  margin: 10px;
  padding: 8px 16px;
}
</style>
```

`frontend/resources/js/components/About.vue`:

```vue
<template>
  <div class="about">
    <h1>About Page</h1>
    <p>This is the About page of our Laravel BFF application.</p>
    <p>Current user: {{ user ? user.name : 'No user logged in' }}</p>
    <router-link to="/">Back to Home</router-link>
  </div>
</template>

<script>
export default {
  name: 'About',
  computed: {
    user() {
      return this.$store.state.user;
    }
  }
}
</script>

<style scoped>
.about {
  padding: 20px;
}
</style>
```

#### 4.9 docker-compose.yml に追加

```yaml
  # Laravel Frontend BFF サーバー
  frontend:
    build:
      context: ./frontend
      dockerfile: Dockerfile
    container_name: app_frontend
    ports:
      - "3000:80"
    volumes:
      - ./frontend:/var/www/html
      - /var/www/html/node_modules
    environment:
      - CHOKIDAR_USEPOLLING=true
      - NODE_ENV=development
    depends_on:
      - backend
    networks:
      - app-network
```

### 5. 最終的な docker-compose.yml

```yaml
version: '3.8'

services:
  # MySQL データベースサーバー
  db:
    image: mysql:8.0
    container_name: app_db
    environment:
      MYSQL_ROOT_PASSWORD: rootpass
      MYSQL_DATABASE: laravel_db
      MYSQL_USER: laravel_user
      MYSQL_PASSWORD: laravel_pass
      TZ: 'Asia/Tokyo'
    ports:
      - "3306:3306"
    volumes:
      - mysql_data:/var/lib/mysql
      - ./docker/mysql/my.cnf:/etc/mysql/conf.d/my.cnf
    networks:
      - app-network

  # Laravel Backend API サーバー
  backend:
    build:
      context: ./backend
      dockerfile: Dockerfile
    container_name: app_backend
    volumes:
      - ./backend:/var/www/html
      - ./docker/apache/000-default.conf:/etc/apache2/sites-available/000-default.conf
    ports:
      - "8080:80"
    depends_on:
      - db
    networks:
      - app-network

  # Laravel Frontend BFF サーバー
  frontend:
    build:
      context: ./frontend
      dockerfile: Dockerfile
    container_name: app_frontend
    ports:
      - "3000:80"
    volumes:
      - ./frontend:/var/www/html
      - /var/www/html/node_modules
    environment:
      - CHOKIDAR_USEPOLLING=true
      - NODE_ENV=development
    depends_on:
      - backend
    networks:
      - app-network

volumes:
  mysql_data:

networks:
  app-network:
    driver: bridge
```

## トラブルシューティング

### 1. Node.js 権限問題

**症状**: `mix: Permission denied`

**原因**: Docker環境でNode.jsモジュールの実行権限が不足

**解決法**:

```bash
# 一時的解決
docker exec app_frontend find /var/www/html/node_modules/.bin -type f -exec chmod +x {} \;

# 根本的解決: Dockerfileに以下を追加
RUN find /var/www/html/node_modules/.bin -type f -exec chmod +x {} \; \
    && find /var/www/html/node_modules -name "*.js" -path "*/bin/*" -exec chmod +x {} \;
```

### 2. Laravel Mix ビルドエラー

**症状**: `Additional dependencies must be installed`

**原因**: vue-loaderが不足

**解決法**:

```bash
# vue-loaderを事前インストール
RUN npm install vue-loader@^15.9.8 --save-dev --legacy-peer-deps
```

### 3. Vue Router 404エラー

**症状**: 直接URL アクセスで `Not Found`

**原因**: SPA用ルート設定不足

**解決法**:

```php
// routes/web.php
Route::get('/{any}', function () {
    return view('welcome');
})->where('any', '.*');
```

### 4. Mix Manifest エラー

**症状**: `The Mix manifest does not exist`

**原因**: アセットがビルドされていない

**解決法**:

```bash
# 手動でアセットビルド
docker exec app_frontend npm run development
```

### 5. Docker Build エラー

**症状**: `parent snapshot does not exist`

**原因**: Docker キャッシュ破損

**解決法**:

```bash
# Docker環境をクリーンアップ
docker system prune -f
docker-compose build --no-cache
```

### 6. MySQL 接続エラー

**症状**: `Connection refused`

**原因**: コンテナ間ネットワーク設定問題

**解決法**:

```yaml
# 全サービスに同じネットワークを設定
networks:
  - app-network
```

### 7. Apache DocumentRoot 問題

**症状**: Laravel が動作しない

**原因**: Apache DocumentRoot が `/var/www/html/public` でない

**解決法**:

```apache
# 000-default.conf
DocumentRoot /var/www/html/public

<Directory /var/www/html/public>
    AllowOverride All
</Directory>
```

## 動作確認方法

### 1. 基本動作確認

```bash
# 全サービス起動
docker-compose up -d

# コンテナ状態確認
docker-compose ps

# ログ確認
docker-compose logs frontend
```

### 2. アクセス確認

- **Frontend**: http://localhost:3000
- **Backend API**: http://localhost:8080
- **MySQL**: localhost:3306

### 3. Vue.js 動作確認

1. http://localhost:3000 にアクセス
2. "Update User" ボタンクリック → Vuex 動作確認
3. "Go to About" クリック → Vue Router 動作確認

### 4. アセット再ビルド

```bash
# 開発時のアセット再ビルド
docker exec app_frontend npm run development

# Watch モード
docker exec app_frontend npm run watch
```

## 開発フロー

### 1. 新しい Vue コンポーネント追加

1. `frontend/resources/js/components/` にコンポーネント作成
2. `frontend/resources/js/app.js` のルートに追加
3. アセット再ビルド

### 2. API エンドポイント追加

1. Backend API: `backend/routes/api.php` にルート追加
2. Frontend BFF: API プロキシの実装
3. Vue.js: API 呼び出し処理の実装

### 3. データベース操作

```bash
# マイグレーション実行
docker exec app_backend php artisan migrate

# シーダー実行
docker exec app_backend php artisan db:seed
```

## 最終的なディレクトリ構造

```
bff-laravel/
├── docker-compose.yml
├── docs/
│   └── SETUP_GUIDE.md
├── docker/
│   ├── mysql/
│   │   └── my.cnf
│   └── apache/
│       └── 000-default.conf
├── backend/                    # Laravel API
│   ├── Dockerfile
│   ├── app/
│   ├── routes/
│   │   ├── api.php
│   │   └── web.php
│   ├── .env
│   └── ...
└── frontend/                   # Laravel BFF + Vue.js
    ├── Dockerfile
    ├── app/
    ├── resources/
    │   ├── js/
    │   │   ├── app.js
    │   │   └── components/
    │   │       ├── Home.vue
    │   │       └── About.vue
    │   └── views/
    │       └── welcome.blade.php
    ├── routes/
    │   ├── web.php
    │   └── api.php
    ├── webpack.mix.js
    ├── package.json
    ├── .env
    └── ...
```

## 参考資料

- [Laravel Documentation](https://laravel.com/docs)
- [Vue.js Guide](https://vuejs.org/v2/guide/)
- [Docker Compose Documentation](https://docs.docker.com/compose/)
- [Laravel Mix Documentation](https://laravel-mix.com/docs)

---

**作成日**: 2025-07-02  
**最終更新**: 2025-07-02