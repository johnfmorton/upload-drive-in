.PHONY: dev

dev:
	ddev launch
	ddev exec npm run dev

.PHONY: build

build:
	ddev start
	ddev exec npm run build
