# YOURLS — adapt-to-it fork

> A self-hosted YOURLS with a Filament-style admin, DB-backed multi-user auth,
> and async click tracking with rich per-link analytics.

This fork ships three additions on top of upstream
[YOURLS/YOURLS](https://github.com/YOURLS/YOURLS):

1. **UI rewrite** — Blade + Tailwind + Alpine, design tokens, component
   library (atoms / molecules / organisms / forms). All admin pages routed
   through Blade, with a legacy fall-through.
2. **DB-backed user management** (DB v509) — replaces the
   `$yourls_user_passwords` config-file array with a `yourls_users` table
   carrying roles (`admin` / `editor`), versioned API keys, sliding-window
   per-user rate limit, and self-service Profile / admin Users pages.
3. **Extended click tracking** (DB v510) — async logging path (bots get a
   `301` then log; humans optionally get a tiny inline interstitial that
   fires `navigator.sendBeacon` and `location.replace` in parallel), 12 new
   hot columns + a JSON `meta` blob on `yourls_log`, and six analytics tabs
   on the per-link infos page (Overview / Audience / Geography / Sources /
   Technology / Activity, all using the new design system).

Everything else — schema for URLs, the redirect API, the plugin system, the
cookie format — is unchanged from upstream and stays compatible.

> [!IMPORTANT]
> This fork is **not** affiliated with the upstream YOURLS project and the
> additions here are **not** intended to be merged upstream. It exists for
> the use cases where the bundled feature set makes sense as a single
> install. If you only want a subset, you're better off using upstream
> YOURLS plus a plugin.

## Quick start with Docker

The repo ships a self-contained dev stack — useful for local development,
running the test suite, or evaluating the fork before deploying to your
own server.

```bash
git clone https://github.com/adapt-to-it/YOURLS.git
cd YOURLS
docker compose up -d
```

- Web UI at <http://localhost:8080/admin/> — login `admin` / `changeme`
- MariaDB exposed on host port `33306` (user `yourls` / pass `yourls`)
- Override defaults via the `environment:` block in `docker-compose.yml` or
  via a `.env` file.

Rebuild the compiled Tailwind/Alpine bundle when you edit views or tokens:

```bash
docker compose run --rm assets
```

## Production install

Drop the contents of the repo into your `public_html/` (preserving your
existing `user/config.php`), then visit `/admin/upgrade.php` once. The
schema migration is idempotent and runs both the v509 (users) and v510
(click tracking) bumps in one pass.

Backup your DB first. The migration is non-destructive (it only adds tables
and columns) but a backup is the standard precaution.

### Required runtime

- PHP **8.1+**
- MySQL **5.7+** or MariaDB **10.2+** (JSON column support is required for
  the v510 `meta` field)
- The runtime dependencies in `includes/vendor/` ship with the repo
  (production-only, no dev deps). No `composer install` required on the
  production host. The compiled CSS/JS bundle in `ui/assets/dist/` also
  ships with the repo.

### Re-installing vendor for development

If you ever need to re-run composer locally (e.g. you bumped a dependency
in `composer.json`), use the right flag for your target:

```bash
# Production install — what ships in the repo
composer install --no-dev

# Development install — adds PHPUnit and friends, lets you run the tests
composer install
```

The repo ships in `--no-dev` shape so the production zip stays small.
PHPUnit is pulled in only when you re-install with dev deps locally.

## Configuration

Optional `define()`s in `user/config.php` — all have safe defaults so the
fork behaves like upstream until you opt in:

| Constant | Default | Purpose |
|----------|---------|---------|
| `YOURLS_CLICK_INTERSTITIAL`     | `false` | Render an inline interstitial for human visitors. When `false` (default), the redirect path is identical to upstream YOURLS. When `true`, humans get a ~1 KB inline page that fires `navigator.sendBeacon` to `yourls-collect.php` in parallel with `location.replace`. |
| `YOURLS_CLICK_ANONYMIZE_IP`     | `false` | Zero last IPv4 octet / last 80 IPv6 bits before insert. |
| `YOURLS_CLICK_BEACON_RATELIMIT` | `60`    | Beacon requests per minute per IP. |
| `YOURLS_CLICK_VISITOR_SALT`     | derived from `YOURLS_COOKIEKEY` | Salt used inside `visitor_hash`. |
| `YOURLS_UI_DISABLE`             | `false` | Force the legacy non-Blade rendering. |
| `YOURLS_UI_LEGACY_ASSETS`       | `true`  | Keep loading the legacy stylesheet alongside the new bundle (helps plugins that target old IDs). |

## What's in each tab on `/<keyword>+`

- **Overview** — KPIs (Total / Unique / Top device / Top country) with
  sparklines, Today / 7d / 30d windows with delta vs previous period,
  Time-to-first-click, 30-day clicks line chart, hour × day-of-week
  heatmap.
- **Audience** — visitors, new vs returning donut, bot vs human gauge,
  device / browser / OS breakdowns, OS × device-type stacked bars,
  unique-visitor growth line, top user-agents table.
- **Geography** — countries reached / coverage of UN states, top
  country/city/continent, tier-1 share, concentration (top-5 + Herfindahl),
  Leaflet world choropleth, multi-line trend for top 5 countries,
  country/continent/city breakdown table.
- **Sources** — direct vs referral, social/search/other split, top social
  platforms and search engines, source category trend (stacked area),
  source × day-of-week heatmap, UTM matrix, top referrers table with
  per-row sparkline.
- **Technology** — average DPR, median viewport, portrait vs landscape,
  top connection type, rendering engine; DPR / orientation / engine donuts,
  top resolutions / connections / languages bars, OS × browser heatmap,
  viewport scatter plot, combined Device · OS · Browser · Engine table.
  Tab includes an honest disclosure of what we *don't* collect (browser/OS
  versions, touch capability, dark-mode preference, cookie/DNT, RAM/CPU).
- **Activity** — paginated raw click table (time, country, city, device,
  browser, OS, referrer host, UTM source).

## Privacy and what we record per click

Every click row carries: timestamp, short URL keyword, referrer URL +
parsed host, user-agent + parsed device/browser/OS family, IP address,
country code (and city/region when the MaxMind GeoLite2-City DB is
present), three UTM fields, a 16-char `visitor_hash` derived from
`sha256(ip + ua + utc_day + cookiekey)` (rotates every UTC day), and a
unique `click_uid`.

When the JS beacon is enabled, the `meta` JSON also gets screen / viewport
sizes, DPR, timezone, language, and `navigator.connection.effectiveType`.

No persistent client cookie is set. No third-party request is made from
the redirect path. Optional IP anonymization is available via the config
constant above. The visitor hash makes "unique visitors" computable
without storing PII beyond what upstream YOURLS already stores.

## Testing

```bash
# Run the click-tracking PHPUnit suite
docker compose exec web includes/vendor/bin/phpunit \
    --bootstrap tests/bootstrap.php tests/tests/click/

# Run the user-management suite
docker compose exec web includes/vendor/bin/phpunit \
    --bootstrap tests/bootstrap.php tests/tests/users/

# Run the five end-to-end integration scenarios
# (clean install / upgrade from 508 / upgrade from 509 / 500 mixed clicks /
# 50 beacon submissions)
docker compose exec web php tests/scenarios/click-tracking-scenarios.php
```

## Hooks added by the fork

- `click_payload` (filter) — modify the `\YOURLS\Click\ClickPayload`
  before insert.
- `click_is_bot` (filter) — override bot detection.
- `click_beacon_received` (action) — fired after beacon validation.
- `click_interstitial_html` (filter) — replace the interstitial body.
- `clicks_aggregate_query` (filter) — modify the SQL used by the per-tab
  aggregations.
- `user_can` (filter) — override role/capability checks.

The existing upstream hooks (`shunt_log_redirect`, `log_redirect`,
`pre_yourls_info_*`, etc.) are preserved.

## Documentation in this repo

- [`docs/click-tracking.md`](docs/click-tracking.md) — operator-facing
  config + hooks reference for stack 3.
- [`ui/README.md`](ui/README.md) — UI rewrite overview (component library,
  design tokens, plugin author notes).
- [`docker/README.md`](docker/README.md) — dev stack notes.
- [`docs/superpowers/specs/`](docs/superpowers/specs/) — design specs.
- [`docs/superpowers/plans/`](docs/superpowers/plans/) — implementation plans.

## License

Same as upstream YOURLS: [MIT](LICENSE). All additions in this fork are
released under the same terms.

## Acknowledgements

Built on top of [YOURLS/YOURLS](https://github.com/YOURLS/YOURLS) by Ozh
and contributors. The world map uses
[Leaflet](https://leafletjs.com/) +
[topojson-client](https://github.com/topojson/topojson-client) +
[world-atlas](https://github.com/topojson/world-atlas), all loaded from
jsdelivr with SRI integrity hashes. The Filament design system inspired
the look and naming of the component library.
