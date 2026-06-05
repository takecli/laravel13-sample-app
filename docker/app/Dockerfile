FROM php:8.5-fpm-bookworm

# 作業ディレクトリ（nginx / compose の working_dir と合わせる）
WORKDIR /work

# 必要な OS パッケージと PHP 拡張のビルドに必要なライブラリ
RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libzip-dev \
        libpng-dev \
        libjpeg-dev \
        libfreetype6-dev \
        libonig-dev \
        libicu-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql \
        mbstring \
        bcmath \
        gd \
        zip \
        exif \
        pcntl \
        intl \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Composer をインストール（公式イメージからバイナリをコピー）
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

EXPOSE 9000

CMD ["php-fpm"]
