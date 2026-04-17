-include api/.env
export

up:
	cp api/.env.example api/.env

	docker compose down && docker compose up --build -d

	@echo "Waiting for API container..."
	@until docker compose exec api php -v >/dev/null 2>&1; do \
		sleep 2; \
	done

	@echo "Running migrations..."
	docker compose exec api php artisan migrate:fresh --seed

	@echo "Restarting queue worker..."
	docker compose restart queue

	@echo "Waiting for frontend (Vite)..."
	@until curl -s http://127.0.0.1:5173 >/dev/null; do \
		sleep 2; \
	done

	@echo "Opening browser..."
	open http://127.0.0.1:5173 || xdg-open http://127.0.0.1:5173 || start http://127.0.0.1:5173

refresh:
	docker compose exec api php artisan migrate:fresh --seed

test:
	docker compose exec api php artisan test