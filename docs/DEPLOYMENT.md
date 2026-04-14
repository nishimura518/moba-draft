# 本番公開のための資料（ホスティング未契約の場合）

このドキュメントは、**インターネット上にこの Laravel アプリを公開する**ために必要な考え方と作業をまとめたものです。別の AI や担当者に引き継ぐときのコンテキストとしても使えます。

---

## 1. 結論：サーバー（ホスティング）は必要か

**はい。** ブラウザから誰でもアクセスできるようにするには、次のいずれかが必要です。

- **アプリケーションを動かすサーバ**（PHP が実行でき、Web サーバから Laravel にリクエストが届く）
- **データベース**（MySQL / MariaDB / PostgreSQL など。開発で SQLite の場合は本番用に RDBMS に切り替えることが多い）
- **HTTPS**（本番では証明書付きの TLS。多くのホストで無料 Let’s Encrypt 等が利用可能）

「レンタルサーバを持っていない」= **まだ契約していないだけ**で、無料〜低価格の選択肢があります。

---

## 2. 何も持っていない場合の進め方（おすすめの流れ）

1. **要件を決める**（下記「3. このプロジェクトで最低限必要なもの」）
2. **ホスティングの形を選ぶ**（下記「4. 選択肢の比較」）
3. **リポジトリをデプロイ**（Git push、または ZIP アップロード）
4. **本番用 `.env` を設定**（下記「5. 本番 `.env` チェックリスト」）
5. **`composer install`（本番向け）・`php artisan migrate`・キャッシュ最適化**
6. **スケジューラ**（部屋の期限切れ削除 `rooms:prune-expired`）を動かす
7. **動作確認**（部屋作成・参加・ドラフト・HTTPS）

---

## 3. このプロジェクトで最低限必要なもの（技術スタック）

| 項目 | 内容 |
|------|------|
| PHP | **8.2 以上**（`composer.json` の `^8.2`） |
| 拡張 | 一般的な Laravel 用（`mbstring`、`openssl`、`pdo`、利用 DB 用ドライバなど） |
| Composer | サーバ上または CI で依存関係インストール |
| データベース | 本番は **MySQL / MariaDB / PostgreSQL** 推奨（SQLite は小規模・単一サーバ向け） |
| Web サーバ | **nginx または Apache**（ドキュメントルートを `public/` に向ける） |
| スケジュール | **毎分 `php artisan schedule:run`** または **`php artisan schedule:work` の常駐**（期限切れ部屋削除用） |

**ドキュメントルート**は必ず **`public/`**（`index.php` があるディレクトリ）。プロジェクトルートをそのまま公開しないこと。

---

## 4. ホスティングの選択肢（レンタルサーバ「無し」のとき）

### A. PaaS（おすすめ：手間が比較的少ない）

- **Render / Railway / Fly.io** など  
  - Git 連携でデプロイしやすい  
  - 無料枠はスリープ・制限ありのことが多い（用途次第）  
  - スケジューラはサービス側の「Cron Jobs」や別ワーカーで `schedule:run` を用意する必要がある場合あり  

### B. VPS + 自分で nginx（柔軟・学習コスト高）

- **DigitalOcean / Linode / Vultr / ConoHa VPS / さくら VPS** など  
  - OS に PHP・Composer・DB・nginx を入れる  
  - **Laravel Forge**（有料）を使うと VPS 上のデプロイ・SSL・cron がかなり楽  

### C. 日本の共有レンタルサーバ（安いが Laravel に注意）

- **ロリポップ・エックスサーバ・さくらのレンタル** など  
  - **PHP バージョンが 8.2 以上か**、**SSH で `composer` / `artisan` が使えるか**を必ず確認  
  - `public` をドキュメントルートにできないプランでは設定が難しいことがある  

### D. 静的サイトホスティングだけでは足りない

- **GitHub Pages 等の静的ホスティングだけ**では、PHP の Laravel はそのままでは動きません（別途 API サーバが必要）。

---

## 5. 本番 `.env` チェックリスト（このリポジトリで特に重要）

以下は **本番サーバの環境変数**（または `.env`）に設定します。`.env.example` に近いキー名が使われています。

| キー | 推奨 |
|------|------|
| `APP_ENV` | `production` |
| `APP_DEBUG` | **`false`**（エラー詳細を公開しない） |
| `APP_KEY` | `php artisan key:generate` で本番用を生成（空のままにしない） |
| `APP_URL` | **`https://あなたのドメイン`** |
| `DB_*` | 本番データベース接続 |
| `SESSION_SECURE_COOKIE` | **`true`**（HTTPS 前提） |
| `TRUSTED_PROXIES` | リバースプロキシ（Cloudflare / ロードバランサ）利用時は `*` またはプロキシ IP のカンマ区切り |
| `MOBA_ROOM_SLIDING_TTL_HOURS` | 部屋の寿命延長（デフォルト 72 など） |
| `MOBA_MAX_ROOMS_PER_IP_PER_HOUR` | 同一 IP の部屋作成上限 |

アプリ内では **`APP_ENV=production` のとき HTTPS を強制**する実装があります（`AppServiceProvider`）。

---

## 6. デプロイ後に必ず実行する Artisan コマンド（例）

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

（`config:cache` 後は `.env` を変えたら再度 `config:cache` が必要、と覚えておく。）

ストレージ・ログを公開ディレクトリ外に置く場合は `php artisan storage:link` が必要な構成もあるが、このプロジェクトがファイルアップロードを主にしない場合は運用次第。

---

## 7. スケジューラ（期限切れ部屋の削除）

スケジュール定義は **`bootstrap/app.php` の `withSchedule`** にあり、**毎時** `php artisan rooms:prune-expired` が走る想定です。

**本番で必要なのは次のいずれかです。**

1. **cron（推奨）**  
   サーバの crontab に例えば次を1行追加（パスは環境に合わせる）:

   ```cron
   * * * * * cd /path/to/moba-draft && php artisan schedule:run >> /dev/null 2>&1
   ```

2. **常駐プロセス**  
   `php artisan schedule:work` を Supervisor / systemd で常時起動（cron が使えない環境向け）。

**cron を設定できない共有サーバ**の場合は、コントロールパネルの「cron 設定」があるか、または VPS / PaaS に移すかを検討してください。

---

## 8. セキュリティ関連（このリポジトリに既に含まれるもの・本番側のもの）

**コード側（既存）**

- セキュリティヘッダ（CSP 含む。インライン script は許容設定のため完全な CSP ではない）
- API / Web のレート制限
- 部屋の有効期限・同一 IP の部屋作成上限
- `player_token` の HttpOnly Cookie 等

**本番インフラ側（ホスティングで行う）**

- TLS 証明書（Let’s Encrypt 等）
- ファイアウォール・OS の更新
- DB のバックアップ
- （必要なら）WAF・DDoS 対策

---

## 9. ChatGPT 等に引き継ぐときに貼ると良い一文

> Laravel 12、PHP 8.2、フロントは Blade + 同一オリジンの fetch で `/api` にアクセス。本番は `public` をドキュメントルートにし、`APP_DEBUG=false`、HTTPS、`php artisan migrate`、および **毎分 `php artisan schedule:run`**（または `schedule:work`）が必要。DB は MySQL 等を想定。詳細はリポジトリの `docs/DEPLOYMENT.md` を参照。

---

## 10. Render.com で Web Service を作る場合（重要）

Render の **New Web Service** はリポジトリを検出すると **Node / `yarn build`** を自動提案しますが、このプロジェクトは **Laravel（PHP）** です。**Node のままでは動きません。**

### ダッシュボードで選ぶこと

| 項目 | 設定 |
|------|------|
| **Language / Environment** | **Docker**（「Node」ではない） |
| **Branch** | `main`（利用中のブランチ） |
| **Root Directory** | 空欄（モノレポでない限り） |
| **Build Command** | **空欄**（Dockerfile でビルドするため。Node 用の `yarn install; yarn build` は削除） |
| **Start Command** | **空欄**（`Dockerfile` の `CMD` が使われます） |

リポジトリには **`Dockerfile`** と **`scripts/00-laravel-deploy.sh`** を追加済みです（[Render の Laravel + Docker 手順](https://render.com/docs/deploy-php-laravel-docker) と同系統の nginx-php-fpm イメージ）。Vite 用に **マルチステージビルド**で `npm run build` も実行します。

### データベース

- Render 上で **PostgreSQL** を新規作成し、**Internal Database URL** を Web Service の環境変数に渡します。
- Web Service の **Environment** に少なくとも次を設定します。

| 変数名 | 例・説明 |
|--------|----------|
| `APP_KEY` | ローカルで `php artisan key:generate --show` の出力（`base64:...`） |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_URL` | `https://（Render が付与するドメイン）` |
| `DB_CONNECTION` | `pgsql` |
| `DATABASE_URL` | Render の Postgres の **Internal** URL（ダッシュボードからコピー） |
| `TRUSTED_PROXIES` | `*`（Render のプロキシ経由で HTTPS を正しく扱うため） |
| `SESSION_DRIVER` | `database` のままなら、マイグレーションで `sessions` テーブルが作成されます |
| `SESSION_SECURE_COOKIE` | `true` |

※ `config/database.php` では PostgreSQL の接続 URL に **`DATABASE_URL`** を解釈できるようにしてあります。

### スケジューラ（期限切れ部屋の削除）

`schedule:run` を毎分動かすには、Render の **Cron Job** や **別ワーカー**で `php artisan schedule:run` / `schedule:work` を実行する必要があります。最小構成では手動で `php artisan rooms:prune-expired` を実行する方法もあります。運用方針が決まったら [Render のドキュメント](https://render.com/docs/cronjobs) を参照してください。

### デプロイ後

Git に **`package-lock.json` が無い**場合、Docker ビルドで `npm install` が毎回走ります。安定させるにはローカルで `npm install` を実行し、生成された `package-lock.json` をコミットするとよいです。

---

## 11. 次のアクション（最短）

1. 予算と「SSH の有無」「PHP 8.2 の可否」を決める  
2. 上記 **4. の A〜C** から一つ選ぶ  
3. 選んだサービスの公式ドキュメントに沿って **PHP + Web サーバ + DB** を用意し、このリポジトリをデプロイする  

質問を続ける場合は、**希望の月額予算**と **ドメインを持っているか** を書くと、候補をさらに絞りやすくなります。
