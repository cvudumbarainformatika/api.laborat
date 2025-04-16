redis-monitor:
	docker compose exec redis redis-cli MONITOR

redis-info:
	docker compose exec redis redis-cli INFO

redis-memory:
	docker compose exec redis redis-cli INFO memory

redis-clients:
	docker compose exec redis redis-cli CLIENT LIST

redis-stats:
	@echo "=== Redis Statistics ==="
	@echo "Keys in database:"
	@docker compose exec redis redis-cli DBSIZE
	@echo "\nMemory usage:"
	@docker compose exec redis redis-cli INFO | grep used_memory_human
	@echo "\nConnected clients:"
	@docker compose exec redis redis-cli INFO | grep connected_clients

redis-cli:
	docker compose exec redis redis-cli

redis-flush:
	docker compose exec redis redis-cli FLUSHALL



shell:
	docker compose exec app bash

log:
	docker compose exec app tail -f storage/logs/laravel.log