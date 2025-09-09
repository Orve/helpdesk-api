# helpdesk-api

1. 仕様（スコープ小さめで完成優先）

認証：メール＆パスワード（Laravel Sanctum）

役割：admin / agent / user

エンティティ：

Ticket（title, description, status[open, in_progress, resolved], priority, category_id, user_id, agent_id, attachments…）

Category（name）

Comment（ticket_id, user_id, body, attachments）

機能

ユーザー：チケット作成／自分のチケット一覧／コメント追記／添付（S3）

エージェント：担当アサイン／ステータス変更／検索・フィルタ

管理：カテゴリCRUD、ユーザー管理（簡易）

ダッシュボード：ステータス別件数、直近の更新、担当割り当て

非機能

CORS整備、API バージョニング（/api/v1）

テスト（Feature 最低限）

ログとエラーハンドリング（Laravel標準＋APIレスポンス整形）

2. アーキテクチャ

Frontend：React + TypeScript + Vite、TanStack Query、React Router、Zustand（またはContext）、Axios、Tailwind

Backend：Laravel 11、Sanctum、Laravel Pint、PHP 8.3、MySQL（RDS推奨）、S3（添付保存）

Infra：

Amplify Hosting（React をビルド＆配信）

EC2（Amazon Linux 2023） + Nginx + PHP-FPM（API）

RDS MySQL（本番 DB）

S3（添付ファイル）

Route53/ACM（独自ドメイン & HTTPS、ALB 経由でもOK）

3. リポ構成（2リポ前提）
helpdesk-frontend/   # React
helpdesk-api/        # Laravel

4. API 設計（主要）
POST   /api/v1/auth/register
POST   /api/v1/auth/login
POST   /api/v1/auth/logout

GET    /api/v1/me
GET    /api/v1/dashboard/summary

GET    /api/v1/tickets?status=&q=&page=
POST   /api/v1/tickets
GET    /api/v1/tickets/{id}
PUT    /api/v1/tickets/{id}
POST   /api/v1/tickets/{id}/assign        # agent割当
POST   /api/v1/tickets/{id}/resolve       # 解決
POST   /api/v1/tickets/{id}/comments
POST   /api/v1/uploads                    # S3直 or API経由アップロード

GET    /api/v1/categories
POST   /api/v1/categories  (admin)
PUT    /api/v1/categories/{id} (admin)
DELETE /api/v1/categories/{id} (admin)

5. DB モデル（Eloquent 関係）

User hasMany Ticket（as requester）

User hasMany Ticket（as agent, agent_id）

Ticket belongsTo User（requester）, belongsTo User（agent）, belongsTo Category, hasMany Comment

Comment belongsTo Ticket, belongsTo User

Category hasMany Ticket

6. 実装ステップ（コマンドつき）
Backend（helpdesk-api）
# プロジェクト作成
composer create-project laravel/laravel helpdesk-api
cd helpdesk-api

# 認証・S3・CORS
composer require laravel/sanctum league/flysystem-aws-s3-v3
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"

# モデル&マイグレーション（例）
php artisan make:model Ticket -m
php artisan make:model Category -m
php artisan make:model Comment -m
php artisan make:controller Api/V1/AuthController
php artisan make:controller Api/V1/TicketController
php artisan make:controller Api/V1/CategoryController
php artisan make:controller Api/V1/CommentController


.env（ローカル例）

APP_URL=http://localhost
FRONTEND_ORIGIN=http://localhost:5173
SESSION_DRIVER=cookie
SANCTUM_STATEFUL_DOMAINS=localhost:5173
# DB
DB_CONNECTION=mysql
DB_DATABASE=helpdesk
DB_USERNAME=root
DB_PASSWORD=secret
# S3
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=...
AWS_SECRET_ACCESS_KEY=...
AWS_DEFAULT_REGION=ap-northeast-1
AWS_BUCKET=your-bucket


ルーティング例：routes/api.php に /v1/... を定義、auth:sanctum を必要箇所に付与

CORS：config/cors.php の allowed_origins に Amplify のドメインを追加

シーディング：php artisan make:seeder で Category とデモユーザ投入

テスト：php artisan test（Feature で認証/チケット作成を1本ずつ）

Frontend（helpdesk-frontend）
npm create vite@latest helpdesk-frontend -- --template react-ts
cd helpdesk-frontend
npm i axios @tanstack/react-query react-router-dom zustand
npm i -D tailwindcss postcss autoprefixer
npx tailwindcss init -p


.env（フロント）

VITE_API_BASE_URL=https://api.your-domain.com


Axios 基本設定（Interceptorで withCredentials: true、エラーハンドリング）

画面：

/login /register

/tickets（一覧・検索・フィルタ）

/tickets/:id（詳細＋コメント）

/new（作成）

/dashboard（統計）

/admin/categories

状態：TanStack Query で tickets, ticket(id), me, categories キャッシュ

7. デプロイ手順（本番）
RDS & S3（先に用意）

RDS MySQL（ap-northeast-1）を作成、セキュリティグループでEC2からのみ受け入れ

S3 バケット作成（プライベート、必要に応じてCloudFront）

EC2（API）

EC2（Amazon Linux 2023, t3.small 以上）作成、Elastic IP 付与

セキュリティグループ：80/443（世界）、22（自宅IP）

セットアップ

sudo dnf update -y
sudo dnf install -y nginx git unzip
# PHP 8.3
sudo dnf install -y php php-fpm php-mbstring php-xml php-pdo php-mysqlnd php-curl php-zip
# Composer
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php && sudo mv composer.phar /usr/local/bin/composer
# デプロイ
cd /var/www
sudo git clone https://github.com/you/helpdesk-api.git
cd helpdesk-api
composer install --no-dev --optimize-autoloader
cp .env.example .env  # 本番値に編集（APP_KEY, DB, S3, SANCTUM等）
php artisan key:generate
php artisan migrate --force
php artisan storage:link
sudo chown -R nginx:nginx /var/www/helpdesk-api


Nginx（例）

server {
  listen 80;
  server_name api.your-domain.com;
  root /var/www/helpdesk-api/public;

  index index.php;
  location / {
    try_files $uri /index.php?$query_string;
  }
  location ~ \.php$ {
    include fastcgi_params;
    fastcgi_pass   unix:/run/php-fpm/www.sock;
    fastcgi_param  SCRIPT_FILENAME  $document_root$fastcgi_script_name;
  }
}

sudo systemctl enable --now php-fpm
sudo systemctl enable --now nginx


HTTPS

早道：ALB+ACM を前段に置く（推奨）

直タームなら certbot で Nginx に導入

Amplify（Frontend）

GitHub 連携→ helpdesk-frontend 選択

Build 設定（自動生成でOK）

build:
  commands:
    - npm ci
    - npm run build
    - npm run test --if-present


環境変数：VITE_API_BASE_URL=https://api.your-domain.com

カスタムドメイン（任意）：app.your-domain.com を割り当て

8. CI/CD（任意で簡易）

Frontend：Amplify がブランチ push で自動ビルド

Backend：GitHub Actions で EC2 に SSH デプロイ（composer install, php artisan migrate --force, php artisan config:cache）

9. 最小データ構造（例）
users(id, name, email, password, role)
categories(id, name)
tickets(id, title, description, status, priority, category_id, user_id, agent_id, created_at)
comments(id, ticket_id, user_id, body, created_at)
attachments(id, attachable_type, attachable_id, path, original_name, size)

10. 画面イメージ（要素）

一覧：ステータスChip、優先度Badge、担当者、更新日時、全文検索

詳細：右サイドにメタ情報（カテゴリ/担当/履歴）、下にコメントタイムライン

新規：タイトル・本文・カテゴリ・優先度・添付（ドラッグ&ドロップ）

ダッシュボード：円グラフ（ステータス比率）、折れ線（週次作成数）