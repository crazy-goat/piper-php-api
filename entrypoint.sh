#!/usr/bin/env bash
set -e

# Download missing models (uses voices.json from image)
echo "Checking/downloading models..."
bash download-models.sh

# Start webman server
echo "Starting webman server..."
export OTEL_PHP_AUTOLOAD_ENABLED=true
exec php start.php start
