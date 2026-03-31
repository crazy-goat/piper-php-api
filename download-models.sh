#!/usr/bin/env bash
set -euo pipefail

REPO="rhasspy/piper-voices"
OUT_DIR="${1:-models}"
BRANCH="main"
BASE_URL="https://huggingface.co/$REPO/resolve/$BRANCH"

mkdir -p "$OUT_DIR"

# Use provided voices.json (don't download it)
if [[ ! -f "$OUT_DIR/voices.json" ]]; then
    echo "Error: voices.json not found in $OUT_DIR"
    exit 1
fi

echo "Using existing voices.json ..."

echo "Extracting file paths ..."
jq -r '.[].files | keys[]' "$OUT_DIR/voices.json" | grep -E '\.(onnx|onnx\.json)$' | sort -u | while read -r file; do
    dest="$OUT_DIR/$file"
    mkdir -p "$(dirname "$dest")"

    if [[ -f "$dest" ]]; then
        echo "[SKIP] $file"
        continue
    fi

    echo "[DOWN] $file"
    curl -sL -o "$dest" "$BASE_URL/$file"
done

echo "Done. Models saved to $OUT_DIR"
