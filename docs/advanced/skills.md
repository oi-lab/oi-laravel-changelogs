---
title: AI skills
description: The skill the package ships, and how to install it into a host application.
section: advanced
order: 3
---

# AI skills

The package ships one skill, `oilab-laravel-changelogs`. It teaches an AI
assistant the part of this documentation a rule cannot enforce: when an entry
is owed, why one entry is one subject rather than one commit, the order of
operations that makes the commit hash exist before the entry names it, and the
format `change-log:check` will hold it to.

## Install it into a host application

```bash
php artisan oi:skills oilab-laravel-changelogs --project
```

The command comes from `oi-lab/oi-laravel-development`. It writes the skill
into `.claude/skills/` and adds the package's rules section to the host's
`CLAUDE.md`, so the assistant knows to activate the skill right after
committing a fix — before the task is considered done.

Without that package, this one ships its own equivalent:

```bash
php artisan oi:install-ai-skill
```

## What the skill is told

- Write the entry in the language of `entries.locale`, in the past tense,
  plainly.
- Check the directory for an entry the work belongs inside before opening a new
  one.
- Reuse a tag already in the journal rather than inventing a synonym; the
  vocabulary is printed by `change-log:check`.
- Say what was actually seen, and say why in one clause.
- Put what still has to be done in production in the last bullet.

## Keeping it in sync

The canonical copy is `resources/stubs/ai-skill.md` in the package. After
changing what the package does:

```bash
composer sync-ai-skills
```

which writes `.claude/skills/oilab-laravel-changelogs/SKILL.md` and the
`.junie/` copy inside the package repository. It also runs on every
`composer install` through `post-autoload-dump`.
