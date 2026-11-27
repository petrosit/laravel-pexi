# laravel-pexi

Laravel exception reporting client for Pexi. Every reported exception is sent to `{PEXI_ENDPOINT}/api/reports`.

## Installation

```bash
composer require pexi/laravel
```

Without Packagist, add the repository to `composer.json` first:

```json
"repositories": [{ "type": "vcs", "url": "https://github.com/petrosit/laravel-pexi" }]
```

## Configuration

```dotenv
PEXI_ENABLED=true
PEXI_API_KEY=pxi_...
PEXI_ENDPOINT=https://pexi.on-forge.com
# PEXI_RELEASE=   optional, see below
```

Nothing is sent without an API key and endpoint, and nothing is sent while running tests (unless `PEXI_REPORT_IN_TESTS=true`).

### Release

Each report carries a release. It is resolved in this order:

1. `PEXI_RELEASE`
2. `config('app.version')`
3. the contents of a `VERSION` file in the project root

So apps that keep their version in `config/app.php` or a `VERSION` file need no `.env` bump on deploy.

Publish the config with `php artisan vendor:publish --tag=pexi-config`.
