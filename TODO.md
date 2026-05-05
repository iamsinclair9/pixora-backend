# Dockerization TODO

## Completed
- [x] Plan approved by user

## Pending
- [ ] Create `Dockerfile.phpfpm` (clean PHP-FPM Dockerfile)
- [ ] Create `Dockerfile.nginx` (Nginx with Laravel conf)
- [ ] Create `docker-compose.yml` (full stack: app, nginx, mysql, redis, phpmyadmin)
- [ ] Create `.env.example` (Docker/Laravel env vars)
- [ ] Create `entrypoint.sh` (Laravel setup: migrate, key gen, perms)
- [ ] Update `nginx.conf` if needed
- [ ] Test: `docker compose up -d --build`
- [ ] Verify services, API endpoints, phpMyAdmin

Next step: Create Dockerfiles.
