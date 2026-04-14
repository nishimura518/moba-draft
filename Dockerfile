# Frontend (Vite) — assets を public/build に出力
FROM node:22-bookworm-slim AS frontend
WORKDIR /app
COPY package.json package-lock.json* ./
RUN npm install
COPY . .
RUN npm run build

# Laravel + nginx + php-fpm（Render 公式例と同系統）
FROM richarvey/nginx-php-fpm:3.1.6

WORKDIR /var/www/html
COPY . .
COPY --from=frontend /app/public/build ./public/build

RUN chmod +x scripts/00-laravel-deploy.sh 2>/dev/null || true

ENV SKIP_COMPOSER=1
ENV WEBROOT=/var/www/html/public
ENV PHP_ERRORS_STDERR=1
ENV RUN_SCRIPTS=1
ENV REAL_IP_HEADER=1

ENV APP_ENV=production
ENV APP_DEBUG=false
ENV LOG_CHANNEL=stderr

ENV COMPOSER_ALLOW_SUPERUSER=1

CMD ["/start.sh"]
