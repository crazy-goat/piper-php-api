FROM php:8.4-cli

RUN apt-get update && apt-get install -y --no-install-recommends \
    unzip \
    curl \
    libffi-dev \
    jq \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install ffi pcntl

WORKDIR /app

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction && \
    vendor/bin/piper-tts install-deps && \
    apt-get update && apt-get install -y --no-install-recommends espeak-ng-data && \
    apt-get purge -y unzip && apt-get autoremove -y && \
    rm -rf /var/lib/apt/lists/*

COPY app/ app/
COPY config/ config/
COPY support/ support/
COPY start.php ./
COPY download-models.sh ./
COPY entrypoint.sh ./
RUN chmod +x entrypoint.sh download-models.sh

EXPOSE 8000

ENTRYPOINT ["./entrypoint.sh"]
