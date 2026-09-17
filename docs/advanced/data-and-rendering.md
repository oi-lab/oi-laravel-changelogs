---
title: Data and rendering
description: The two Data shapes, the CommonMark converter, and where the HTML is built.
section: advanced
order: 2
---

# Data and rendering

## The objects

| Class | Role |
| --- | --- |
| `Services\ChangeLogEntry` | one parsed file — a readonly value object, not a model |
| `Services\ChangeLogRepository` | parses the directory, caches it, answers the list and the neighbours |
| `Services\ChangeLogMarkdown` | the CommonMark converter |
| `Data\ChangeLogRowData` | one row of the list |
| `Data\ChangeLogEntryData` | the entry being read |
| `Data\ChangeLogAnswerData` | what `show` answers a fetch with: entry, previous, next |
| `Enums\ChangeLogType` | `fix` or `improvement` |
| `OiLaravelChangelogs` | everything read from the configuration |

Both services are singletons. The converter is expensive to build and holds
nothing request-specific; the repository holds nothing at all, since its cache
is the application's. Under Octane the container survives the request, and that
is exactly what makes keeping them safe.

## Why the enum has two cases

A journal read to answer "what changed since last week" is read by the badge on
the row, and a scale of five kinds is a scale nobody classifies twice the same
way. Anything that is neither a repair nor an improvement is not worth an
entry.

`label()` goes through the translator, and the package ships French alongside
English. A host with its own `Fix` / `Improvement` translations wins, since the
application's own JSON translations are consulted first.

## Where the HTML is built

By default, in Laravel. What goes through the converter is a file of the
repository, written by the people who write the application and never by a
reader — so there is no markdown renderer to ship to the browser, and one round
trip less.

Raw HTML is **escaped** and unsafe links are dropped:

```php
new GithubFlavoredMarkdownConverter([
    'html_input' => 'escape',
    'allow_unsafe_links' => false,
]);
```

A change log is prose and a bullet list. There is no markup to let through, and
the narrower setting is the one to keep by default.

Set `rendering.markdown_engine` to `client` and the server sends the raw
markdown instead, for `react-markdown` to convert. Exactly one of `html` and
`markdown` is ever filled on `ChangeLogEntryData`: sending both would double
the payload of the longest thing on the screen for a component that can only
draw one of them, and leaving the choice to the browser would put the decision
in two places.

## Generating the TypeScript

The Data classes are what `oi-laravel-ts` reads, so the published `types.ts`
can be replaced by generated interfaces:

```bash
php artisan oi:gen-ts
```

Mind the nullability convention: `html` and `markdown` are `?string`, which is
a value that may be null — not a key that may be absent.
