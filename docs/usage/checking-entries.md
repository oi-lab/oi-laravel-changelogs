---
title: Checking entries
description: What change-log:check enforces, and why it belongs in the test suite.
section: usage
order: 3
---

# Checking entries

```bash
php artisan change-log:check
```

It reads every file of the change log directory and names everything wrong with
each one, then exits non-zero if anything was.

## Why it exists

The screen **skips** a file it cannot parse rather than throwing on it: a
journal that returns a 500 because somebody mistyped a date is worse than a
journal missing one entry. Something has to say the entry went missing, and
this is it.

Put it in the test suite, and a file that would silently vanish from the screen
fails the build instead:

```php
it('keeps every change log entry well formed', function () {
    $this->withoutMockingConsoleOutput();

    expect($this->artisan('change-log:check'))->toBe(0);
});
```

## What it enforces

| | |
| --- | --- |
| File name | `YYYY-MM-DD-slug.md`, lowercase, the date matching `date:` |
| Frontmatter | a closed `---` block parsing to a map of keys |
| `title` | present and not blank |
| `date` | a date, and the one the file name opens with |
| `type` | `fix` or `improvement` |
| `tags` | between one and `entries.max_tags`, each lowercase and hyphenated |
| `commits` | a list, each entry a hash of 7 to 40 hex characters |
| Opening paragraph | present, and within `entries.summary_words` |
| Body | at least one `-` bullet after the opening paragraph |
| Placeholders | no `TODO` left in the text |

Accented tags are allowed: a journal names its subjects in its own language.

## The vocabulary report

A successful run ends by printing the tags in use and how many entries each one
holds:

```
  ⇂ console (9)
  ⇂ printing (4)
  ⇂ teams (3)
```

Nothing there is an error. It is the one thing no rule can catch: a tag sitting
alone next to its plural or its unaccented twin is a typo you can see at a
glance, and a journal is small enough for that to be the whole of the guard it
needs.
