# Laravel BFF + Vue.js 開発環境

現代的なWebアプリケーション開発のためのBFF (Backend for Frontend) アーキテクチャを採用した開発環境です。

## 🏗️ アーキテクチャ

```
Browser → Vue.js (SPA) → Laravel BFF (Port 3000) → Laravel API (Port 8080) → MySQL (Port 3306)
```

### 技術スタック

| Layer | Technology | Port | Description |
|-------|------------|------|-------------|
| **Frontend** | Vue.js 2 + Vue Router + Vuex | 3000 | シングルページアプリケーション |
| **BFF** | Laravel 8 + Laravel Mix | 3000 | フロントエンド専用バックエンド |
| **Backend** | Laravel 8 API | 8080 | RESTful API サーバー |
| **Database** | MySQL 8.0 | 3306 | リレーショナルデータベース |
| **Infrastructure** | Docker + Docker Compose | - | コンテナ化環境 |

## 🚀 クイックスタート

### 前提条件

- Docker
- Docker Compose

### 1. プロジェクトクローンと起動

```bash
# プロジェクトクローン
git clone <repository-url>
cd bff-laravel

# 全サービスをバックグラウンドで起動
docker-compose up -d

# ビルド状況確認
docker-compose ps
```

### 2. アクセス確認

| Service | URL | Description |
|---------|-----|-------------|
| **Frontend** | http://localhost:3000 | Vue.js SPA |
| **Backend API** | http://localhost:8080 | Laravel API |
| **Database** | localhost:3306 | MySQL |

### 3. 初回セットアップ (必要に応じて)

```bash
# アセット再ビルド
docker exec app_frontend npm run development

# バックエンドマイグレーション
docker exec app_backend php artisan migrate
```

## 📁 プロジェクト構造

```
bff-laravel/
├── docker-compose.yml          # Docker Compose設定
├── docs/                       # ドキュメント
│   └── SETUP_GUIDE.md         # 詳細セットアップガイド
├── docker/                     # Docker設定ファイル
│   ├── mysql/my.cnf           # MySQL設定
│   └── apache/000-default.conf # Apache設定
├── backend/                    # Laravel API
│   ├── Dockerfile
│   ├── app/
│   ├── routes/api.php         # APIルート定義
│   └── ...
└── frontend/                   # Laravel BFF + Vue.js
    ├── Dockerfile
    ├── resources/js/
    │   ├── app.js             # Vue.jsメインファイル
    │   └── components/        # Vueコンポーネント
    ├── webpack.mix.js         # Laravel Mix設定
    └── ...
```

## 🛠️ 開発フロー

### フロントエンド開発

```bash
# Vueコンポーネント開発
# 1. frontend/resources/js/components/ に新しい.vueファイル作成
# 2. frontend/resources/js/app.js にルート追加
# 3. アセット再ビルド
docker exec app_frontend npm run development

# Watch モード (ファイル変更を自動検知)
docker exec app_frontend npm run watch
```

### バックエンド開発

```bash
# API エンドポイント追加
# 1. backend/routes/api.php にルート定義
# 2. コントローラー作成
docker exec app_backend php artisan make:controller Api/YourController

# マイグレーション
docker exec app_backend php artisan make:migration create_your_table
docker exec app_backend php artisan migrate
```

### データベース操作

```bash
# MySQL接続
docker exec -it app_db mysql -u laravel_user -p

# Laravel Tinker
docker exec -it app_backend php artisan tinker
```

## 🎯 利用可能なページ

現在実装されているページ：

- **Home** (`/`) - メインページ、Vuex状態管理のデモ
- **About** (`/about`) - Aboutページ、Vue Routerのデモ

## 🔧 開発コマンド

### Docker操作

```bash
# 全サービス起動
docker-compose up -d

# 特定サービスのみ起動
docker-compose up frontend

# サービス停止
docker-compose down

# ログ確認
docker-compose logs -f frontend

# コンテナ再ビルド
docker-compose build --no-cache
```

### フロントエンド

```bash
# アセットビルド
docker exec app_frontend npm run development    # 開発用
docker exec app_frontend npm run production     # 本番用
docker exec app_frontend npm run watch          # Watch モード

# 依存関係管理
docker exec app_frontend npm install
docker exec app_frontend npm install package-name
```

### バックエンド

```bash
# Artisan コマンド
docker exec app_backend php artisan list
docker exec app_backend php artisan make:model ModelName
docker exec app_backend php artisan migrate
docker exec app_backend php artisan route:list

# Composer
docker exec app_backend composer install
docker exec app_backend composer require package-name
```

## 📋 BFF (Backend for Frontend) パターン

### なぜBFFを採用するか？

1. **フロントエンド最適化**
   - UI要件に特化したAPI設計
   - データ集約とフォーマット変換

2. **セキュリティ**
   - 直接的なバックエンドAPIアクセスの回避
   - 認証・認可の集約

3. **開発効率**
   - フロントエンドとバックエンドの独立開発
   - APIバージョニングの柔軟性

### データフロー例

```javascript
// Vue.js から BFF API 呼び出し
const response = await fetch('/api/users');

// BFF から Backend API 呼び出し (Laravel)
$response = Http::get('http://backend:80/api/users');

// フロントエンド向けにデータ変換
return response()->json([
    'users' => $response->json()['data'],
    'meta' => ['total' => count($response->json()['data'])]
]);
```

## 🐛 トラブルシューティング

### よくある問題と解決法

#### 1. アセットビルドエラー

```bash
# 権限問題の場合
docker exec app_frontend find /var/www/html/node_modules/.bin -type f -exec chmod +x {} \;

# 依存関係問題の場合
docker exec app_frontend npm install
docker exec app_frontend npm run development
```

#### 2. Vue Router 404エラー

直接URLアクセス時の404エラーは、Laravel側のSPA設定で解決済みです。

#### 3. データベース接続エラー

```bash
# コンテナ起動順序の確認
docker-compose up db backend frontend

# 環境変数の確認
docker exec app_backend cat .env | grep DB_
```

#### 4. Docker Build エラー

```bash
# Docker環境のクリーンアップ
docker system prune -f
docker-compose down
docker-compose build --no-cache
docker-compose up -d
```

## 📖 詳細ドキュメント

詳細な環境構築手順、トラブルシューティング、設定方法については以下を参照してください：

- [📋 詳細セットアップガイド](docs/SETUP_GUIDE.md)

## 🎯 次のステップ

この環境をベースに以下の機能を実装できます：

- [ ] ユーザー認証システム
- [ ] REST API CRUD操作
- [ ] リアルタイム通信 (WebSocket)
- [ ] ファイルアップロード
- [ ] メール送信機能
- [ ] 検索・フィルタリング
- [ ] ページネーション
- [ ] 国際化 (i18n)
- [ ] テスト環境構築
- [ ] CI/CD パイプライン

## 🤝 コントリビューション

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📄 ライセンス

このプロジェクトは MIT ライセンスの下で公開されています。詳細は [LICENSE](LICENSE) ファイルを参照してください。

## 📞 サポート

質問やバグレポートは [Issues](../../issues) にて受け付けています。

---

**開発環境**: Docker + Laravel 8 + Vue.js 2  
**最終更新**: 2025-07-02