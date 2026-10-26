# =============================================================================
# Master Gombit - PHP-FPM + Nginx birleşik imaj (lokal eğitim amaçlı)
# Çok aşamalı build: composer bağımlılıkları build-time indirilir,
# runtime imajı minimal tutulur.
# =============================================================================

FROM composer:2 AS vendor
WORKDIR /build
COPY composer.json ./
# composer.lock varsa o kullanılır (supply-chain: lock file zorunlu önerilir)
RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist --optimize-autoloader --audit || composer install --no-dev --no-scripts --no-interaction --prefer-dist --optimize-autoloader
COPY app ./app
COPY config ./config

FROM php:8.3-fpm-alpine AS runtime

# Güvenli PHP eklentileri: pdo_mysql zorunlu; sodium Argon2id için
RUN docker-php-ext-install pdo_mysql \
 && php -m | grep -q sodium || true

# Nginx (alpine repo)
RUN apk add --no-cache nginx curl \
 && mkdir -p /run/nginx

COPY --from=vendor /build/vendor /var/www/html/vendor
COPY app        /var/www/html/app
COPY config     /var/www/html/config
COPY database   /var/www/html/database
COPY labs       /var/www/html/labs
COPY public     /var/www/html/public
COPY nginx/default.conf /etc/nginx/http.d/default.conf
COPY php/conf.d/security.ini /usr/local/etc/php/conf.d/99-security.ini
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh

# Sabit UID/GID ( Least privilege container user )
RUN addgroup -g 10001 mg && adduser -D -u 10001 -G mg mgweb \
 && chown -R mgweb:mg /var/www/html \
 && mkdir -p /var/www/html/storage/uploads /var/www/html/storage/logs \
 && chown -R mgweb:mg /var/www/html/storage \
 && chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=3s --retries=3 \
  CMD curl -fsS http://127.0.0.1/health.php || exit 1

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
