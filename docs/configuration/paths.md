---
title: Paths and the screen
description: The three configurable paths, and what each decides beyond where a file lands.
section: configuration
order: 3
---

# Paths and the screen

Three paths are configurable, and two of them decide more than where a file
lands.

## `changelog_path`

Relative to the base path, so a test can point it at its own fixtures with a
single `config()` call:

```php
config()->set('oi-laravel-changelogs.changelog_path', 'tests/fixtures/change-logs');
```

Only `*.md` files are read; anything else in the directory is ignored.

## `components_path`

Where the installer writes the React components — and the `@/` alias they
import each other by. The installer derives it: anything under `resources/js/`
becomes `@/` plus the rest, so

```php
'components_path' => 'resources/js/features/journal',
```

rewrites every `@/components/change-logs/...` import in the published files to
`@/features/journal/...`. A path outside `resources/js/` has no alias to speak
of, and the installer says so rather than writing imports that cannot resolve.

## `pages_path`

Where the page lands, **and** the Inertia component the controller renders. The
component name is the path with `resources/js/pages/` cut off it:

| `pages_path` | Component rendered |
| --- | --- |
| `resources/js/pages/change-logs` | `change-logs/index` |
| `resources/js/pages/console/change-logs` | `console/change-logs/index` |

So moving the journal into your console space is one config key and no code
edit. Re-run `php artisan change-log:install --force` to move the file too, or
move it yourself.

## The paths the installer does not ask about

Two helpers and the layout go to fixed locations, because they are named by
their conventional homes rather than by this package:

```
resources/js/lib/change-log-typography.ts
resources/js/lib/change-log-date.ts
resources/js/layouts/change-logs-layout.tsx
```

Move them afterwards if your application keeps them elsewhere; only the imports
at the top of the published components have to follow.
