.PHONY: dev build init

dev:
	ddev launch
	ddev exec npm run dev

build:
	ddev start
	ddev exec npm run build

init:
	@echo "Setting up the project..."
	@if [ ! -f .env ]; then \\
		cp .env.example .env; \\
		echo ".env file created from .env.example. Please configure your database and other settings."; \\
	fi
	ddev exec composer install
	ddev exec php artisan key:generate
	ddev exec npm install
	ddev exec npm run build
	ddev exec php artisan migrate --seed
	@echo "\\nSetup complete."
	@echo "--------------------------------------------------"
	@echo "Next steps:"
	@echo "1. Configure your '.env' file with database credentials, mail server, etc."
	@echo "2. Run 'ddev exec php artisan user:set-role <your-email> admin' to create an admin user."
	@echo "3. Start the development server using 'make dev'."
	@echo "--------------------------------------------------"
