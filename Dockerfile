FROM php:8.3-cli-bookworm

# System libraries + PHP extensions this app actually needs:
# gd (phpoffice/phpspreadsheet, via maatwebsite/excel), pdo_pgsql (Postgres),
# zip (Excel import/export), bcmath (Laravel default recommendation).
RUN apt-get update && apt-get install -y \
        libpng-dev libjpeg-dev libfreetype6-dev libzip-dev libpq-dev unzip git curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" gd pdo_pgsql pgsql zip bcmath \
    && rm -rf /var/lib/apt/lists/*

# Node, for the Vite asset build.
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction
RUN npm ci && npm run build

# Railway injects $PORT at runtime; migrate runs on every boot (idempotent —
# safe since there's no separate release-phase mechanism set up).
CMD php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=${PORT:-8080}
