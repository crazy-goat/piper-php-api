# Piper TTS — PHP Text-to-Speech

Text-to-speech web application powered by [Piper](https://github.com/rhasspy/piper) and [piper-php](https://github.com/nunomaduro/piper-php). Runs entirely on PHP with Workerman as the HTTP server — no Node.js or Python backend required. No GPU needed, everything runs on CPU.

## Demo

🎧 **Try it live:** [https://piper.crazy-goat.com/](https://piper.crazy-goat.com/)

## Features

- **40+ languages** supported
- **Streaming audio** — hear results as they're generated
- **Adjustable speed** — 0.5x to 2x
- **No GPU required** — runs entirely on CPU
- **Zero external dependencies** — no Node.js, no Python

## Quick Start

```bash
docker compose up -d
```

Then open `http://localhost:8000`.

## Requirements

- Docker & Docker Compose
- ~500MB+ disk space for voice models (downloaded on first run)

## License

MIT
