---
title: Installation
description: Install the package, run the wizard, and read the journal.
section: getting-started
order: 2
---

# Installation

## Requirements

- PHP 8.2+
- Laravel 11, 12 or 13
- `inertiajs/inertia-laravel` and a React front end, to use the published
  screen

## Install the package

```bash
composer require oi-lab/oi-laravel-changelogs
```

The service provider registers itself, merges its configuration and loads its
two routes — so `/change-logs` answers before anything has been published. What
it cannot do on its own is draw the screen: that is a React page, and it has to
live in your application.

## Run the installer

```bash
php artisan change-log:install
```

It asks three things and acts on all three:

1. **Who may read the journal.** Public, authenticated, or a middleware you
   name. The answer is written into `route.middleware`.
2. **Where the markdown is converted.** Server-side with `league/commonmark`,
   which needs no npm package, or client-side with `react-markdown`. Only the
   renderer you choose is installed.
3. **Whether Inertia runs with SSR, and whether to use Shadcn's `typeset`
   typography class** instead of `prose`.

Then it publishes the config, creates the change log directory with one example
entry, writes the React files, and offers to add the Shadcn UI components the
screen draws with (`badge`, `button`, `empty`, `item`, `skeleton`).

```bash
npm run build
```

Visit `/change-logs`.

## What was written

```
config/oi-laravel-changelogs.php
resources/markdown/change-logs/<today>-an-example-entry.md
resources/js/components/change-logs/
resources/js/lib/change-log-typography.ts
resources/js/lib/change-log-date.ts
resources/js/layouts/change-logs-layout.tsx
resources/js/pages/change-logs/index.tsx
```

Everything under `resources/js/` is yours from that moment: the package renders
nothing itself, and nothing it published is imported back from `vendor/`.

## Publish only the configuration

```bash
php artisan vendor:publish --tag=oi-laravel-changelogs-config
```

## Delete the example

The installer writes one entry so the screen has something to show on the first
visit. Delete it once you have written your own — `change-log:check` counts it
like any other.
