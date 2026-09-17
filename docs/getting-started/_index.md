---
title: Getting started
description: What the package keeps, and how the journal is read.
section: getting-started
order: 1
---

# Getting started

`oi-lab/oi-laravel-changelogs` keeps the journal of what was fixed and what was
improved in your application. One markdown file per entry, under
`resources/markdown/change-logs/` by default, read on a screen the package
installs into your own front end.

There is no table and no migration. An entry is written in the same pull
request as the work it describes, reviewed with it, and deployed with it — a
database row would have to be seeded from somewhere, and that somewhere would
be these files anyway.

## What it brings

- A reader: two routes, one Inertia page, the list on the left and the entry on
  the right.
- `change-log:make`, which opens an entry with its frontmatter already filled
  in — HEAD included.
- `change-log:check`, which holds every entry to the format and belongs in your
  test suite.
- React stubs you install and then own: no design system of ours, only plain
  Shadcn UI components.

## Where to go next

- [Installation](./installation.md) — the package, the installer, the screen.
- [Writing an entry](../usage/writing-an-entry.md) — the format, and the rule
  that decides how the journal reads.
- [Configuration](../configuration/configuration.md) — every key, and what it
  decides.
