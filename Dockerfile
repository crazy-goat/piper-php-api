FROM php:8.4-cli

RUN apt-get update && apt-get install -y --no-install-recommends \
    curl \
    jq \
    libffi-dev \
    unzip \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install ffi pcntl

RUN pecl install opentelemetry && docker-php-ext-enable opentelemetry

WORKDIR /app

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction && \
    apt-get purge -y unzip && apt-get autoremove -y

RUN vendor/bin/piper-tts install-deps

COPY app/ app/
COPY config/ config/
COPY support/ support/
COPY start.php download-models.sh entrypoint.sh ./
RUN chmod +x entrypoint.sh download-models.sh

RUN mkdir -p models && \
    curl -sL -o models/voices.json https://huggingface.co/rhasspy/piper-voices/resolve/main/voices.json

EXPOSE 8000

HEALTHCHECK --interval=30s --timeout=10s --start-period=60s --retries=3 \
    CMD curl -f http://localhost:8000/api/health || exit 1

ENTRYPOINT ["./entrypoint.sh"]
