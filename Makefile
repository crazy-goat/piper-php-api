IMAGE_APP = piper-app

.PHONY: all build run stop clean

all: build

build:
	docker compose build

run:
	docker compose up -d

stop:
	docker compose down

clean: stop
	docker rmi $(IMAGE_APP) 2>/dev/null || true
