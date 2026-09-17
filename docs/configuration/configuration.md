---
title: Reference
description: Every configuration key with its default and its effect.
section: configuration
order: 2
---

# Reference

| Key | Default | What it decides |
| --- | --- | --- |
| `changelog_path` | `resources/markdown/change-logs` | where the entries live, relative to the base path |
| `components_path` | `resources/js/components/change-logs` | where the published components land, and the `@/` alias they import each other by |
| `pages_path` | `resources/js/pages/change-logs` | where the page lands, **and** the Inertia component the controller renders |
| `route.enabled` | `env('OI_CHANGELOGS_ROUTES', true)` | whether the package registers its own routes |
| `route.prefix` | `change-logs` | the URL the two routes sit under |
| `route.name` | `change-logs.` | the route-name prefix |
| `route.middleware` | `['web']` | who may read the journal |
| `rendering.markdown_engine` | `server` | `server` builds the HTML in Laravel, `client` sends the markdown |
| `rendering.ssr` | `false` | whether the host renders Inertia with SSR |
| `rendering.typeset` | `false` | use Shadcn's `typeset` class instead of `prose` |
| `entries.locale` | `null` | the language entries are written in; null falls back to the app locale |
| `entries.summary_words` | `['min' => 30, 'max' => 75]` | the bounds `change-log:check` holds the opening paragraph to |
| `entries.max_tags` | `5` | how many subjects one entry may be filed under |
| `per_page` | `20` | entries per page of the list |
| `cache.store` | `null` | the store the parse is kept in; null is the application's default |
| `cache.ttl` | `3600` | how long a parse survives once the directory has stopped moving |

## `route`

The two routes are loaded in `boot()`, behind `route.enabled`. Turning it off
keeps everything else — the repository, the controller, the commands — and
leaves the URLs to you. See
[Reading the journal](../usage/reading-the-journal.md#declaring-your-own-routes).

The installer asks who may read the journal and writes the answer into
`route.middleware`. It defaults the question to authenticated: an entry names
the files it touched and the commits it came from, which is not something every
installation wants on a public URL.

## `rendering`

`markdown_engine` decides which of the two content components is installed and
which of `html` and `markdown` the server fills on `ChangeLogEntryData`.
`server` needs no npm package; `client` needs `react-markdown` and
`remark-gfm`, which the installer offers to add.

`ssr` records whether your application renders Inertia server-side. Nothing in
the package branches on it today — it is recorded at install time so the
installer can warn when `resources/js/ssr.tsx` is missing.

`typeset` swaps the typography class the published components apply. It needs
`resources/css/typeset.css` to exist, or the class resolves to nothing.

## `entries`

`locale` is read by `change-log:make` — it writes its placeholders through the
translator — and by the AI skill, which is told to write the entry in that
language. Nothing else depends on it.

`summary_words` and `max_tags` are what `change-log:check` enforces. Thirty
words is a sentence that says what broke; seventy-five is the point past which
nobody reads the column. Five tags is the point past which somebody is
describing the entry rather than filing it.

## `cache`

The parse is cached under a fingerprint of the directory — every markdown
file's name and modification time — so `ttl` only bounds how long a stale parse
could survive a clock that went backwards. There is nothing to clear by hand.
