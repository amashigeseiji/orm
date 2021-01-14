FROM php:8.0

RUN apt-get update -yqq \
    && apt-get install -y \
         git \
         sqlite3 \
         unzip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2.0 /usr/bin/composer /usr/bin/composer
