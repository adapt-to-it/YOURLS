# Local Docker stack for YOURLS + Filament Skin

Spin up YOURLS locally to test the `filament-skin` plugin without touching cPanel.

## Stack

| Service | Image | Port | Notes |
|---|---|---|---|
| `yourls` | custom (php:8.2-apache) | http://localhost:8080 | App + bind mount of the repo |
| `db`     | mariadb:11 | localhost:3307 | Persistent volume `yourls-db-data` |
| `adminer`| adminer:latest | http://localhost:8081 | DB GUI |

## Usage

```bash
# from the repo root
docker compose up -d --build

# open the installer once
open http://localhost:8080/admin/
# username: admin   password: admin
```

The installer runs once and creates the YOURLS tables. After that, log in with
`admin` / `admin` (defined in `docker/yourls-config.php`).

Activate the **Filament Skin** plugin at `/admin/plugins.php` to see the
redesign.

## Why this is isolated

- `Dockerfile` and `docker-compose.yml` live at the repo root but are gated by
  filename — the upstream PHPUnit suite (`tests/`) is untouched.
- The Docker config (`user/config.php`) is bind-mounted **read-only** from
  `docker/yourls-config.php`, so the Docker setup never overwrites a real
  `user/config.php` you might already have on your machine.
- The DB lives in a named Docker volume (`yourls-db-data`) — wipe it with
  `docker compose down -v` for a clean slate.

## Reset

```bash
docker compose down -v   # stops + deletes the DB volume
docker compose up -d     # fresh install
```

## Notes / caveats

- `user/config.php` is mounted **read-only**. YOURLS normally hashes plaintext
  passwords on first login by rewriting the config file — this won't persist
  here. Just keep using `admin/admin` between restarts. If you want hashing to
  persist, remove `:ro` from the volume line in `docker-compose.yml`.
- `YOURLS_DEBUG` is on so errors show up in the browser. Don't ship this config
  to a real server.
- Apache runs as UID/GID 1000 to match common host users; adjust in
  `docker-compose.yml` if your host user has a different UID.
