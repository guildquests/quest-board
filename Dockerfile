# syntax=docker/dockerfile:1

# Production image for Dokploy (Foundation Task 4.1).
# serversideup/php images run as www-data; webroot /var/www/html/public; nginx on 8080.
# Runtime settings (APP_KEY, DB_*, AUTORUN_*, PHP_OPCACHE_ENABLE, HEALTHCHECK_PATH, ...) come from
# environment variables in docker-compose.prod.yml / Dokploy. No .env is baked in.

ARG PHP_IMAGE_TAG=8.4-cli-v5.0.3
ARG WEB_IMAGE_TAG=8.4-fpm-nginx-v5.0.3

############################################
# vendor: production Composer dependencies
############################################
FROM serversideup/php:${PHP_IMAGE_TAG} AS vendor

WORKDIR /var/www/html

# Dependencies first so this layer stays cached when only app code changes.
COPY --chown=www-data:www-data composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-scripts --no-autoloader --prefer-dist

COPY --chown=www-data:www-data . .
RUN composer dump-autoload --optimize --no-dev --no-scripts

############################################
# assets: Vite build (needs PHP + vendor too,
# because the Wayfinder Vite plugin runs Artisan)
############################################
FROM vendor AS assets

USER root
COPY --from=node:24-bookworm-slim /usr/local/bin/node /usr/local/bin/node
COPY --from=node:24-bookworm-slim /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -s ../lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
    && ln -s ../lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx
USER www-data

ENV npm_config_cache=/tmp/.npm

# .npmrc keeps ignore-scripts=true.
RUN npm ci --no-audit --no-fund
RUN npm run build

############################################
# final: fpm-nginx web image
############################################
FROM serversideup/php:${WEB_IMAGE_TAG}

WORKDIR /var/www/html

COPY --chown=www-data:www-data . .
COPY --chown=www-data:www-data --from=vendor /var/www/html/vendor ./vendor
COPY --chown=www-data:www-data --from=assets /var/www/html/public/build ./public/build

RUN php artisan package:discover --ansi
