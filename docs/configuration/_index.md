---
title: Configuration
description: Every key of config/oi-laravel-changelogs.php, and what it decides.
section: configuration
order: 1
---

# Configuration

`config/oi-laravel-changelogs.php` is published by
`php artisan change-log:install` or by
`php artisan vendor:publish --tag=oi-laravel-changelogs-config`. Every key has
a default merged in by the service provider, so the package works before
anything is published.

- [Reference](./configuration.md) — every key, in one table.
- [Paths and the screen](./paths.md) — the three paths, and what each one
  decides beyond where a file lands.
