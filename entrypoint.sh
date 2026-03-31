#!/usr/bin/env bash
set -e

# Check if voices.json exists (required)
if [[ ! -f models/voices.json ]]; then
    echo "Error: models/voices.json not found!"
    echo "Please provide voices.json file in the models directory."
    exit 1
fi

# Download missing models
echo "Checking/downloading models..."
bash download-models.sh

# Start webman server
echo "Starting webman server..."
exec php start.php start
