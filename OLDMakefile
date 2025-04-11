# Makefile for Laravel Application

.PHONY: dev stop

# Start the development environment
dev:
	@echo "Starting the queue worker..."
	php artisan queue:work --daemon &
	@echo "Starting the Laravel development server..."
	php artisan serve --host=127.0.0.1 --port=8000 &
	@echo "Starting the Vite development server..."
	npm run dev &

# Stop all jobs
stop:
	@echo "Stopping all jobs..."
	@pkill -f "php artisan queue:work"
	@pkill -f "php artisan serve"
	@pkill -f "npm run dev"
	@echo "All jobs stopped."
