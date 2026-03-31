#!/usr/bin/env bash
set -e

# Download missing models (uses voices.json from image)
echo "Checking/downloading models..."
bash download-models.sh

# Start webman server
echo "Starting webman server..."
exec php start.php start
