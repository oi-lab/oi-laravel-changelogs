# OI Laravel Changelogs — AI Context

The journal of what was fixed and what was improved in this application: one
markdown file per entry under the configured change log directory, read on a
screen the package installs. No table, no migration — an entry is written in
the same pull request as the work it describes, reviewed with it, and deployed
with it.

**Use this skill whenever a bug has just been fixed, a behaviour corrected or a
feature improved — right after committing the work, before the task is done.**
Also use it when asked to publish, update, check or reformat an entry.

## Core Concepts

| | |
| --- | --- |
| Entries | `config('oi-laravel-changelogs.changelog_path')`, default `resources/markdown/change-logs/*.md` |
| Reading | `OiLab\OiLaravelChangelogs\Services\ChangeLogRepository` |
| One entry | `OiLab\OiLaravelChangelogs\Services\ChangeLogEntry` (parsed file) |
| On the wire | `Data\ChangeLogRowData` (the list) and `Data\ChangeLogEntryData` (the entry) |
| Kinds | `Enums\ChangeLogType`: `fix` or `improvement`, and no third case |
| Screen | `Http\Controllers\ChangeLogController`, routes `change-logs.index` / `change-logs.show` |
| Vocabulary | printed by `php artisan change-log:check` |

Nothing generated has to be regenerated: the repository fingerprints the
directory, so a file added is a file shown. There is no `navigation.json`
equivalent here on purpose.

### When an entry is owed

Write one when a commit **changes what somebody using the application
experiences**: a bug fixed, a wrong behaviour corrected, an interaction made
better, a screen that now shows something it did not.

Do **not** write one for a refactor with no visible effect, a dependency bump,
a test-only change, a comment or a typo. A journal padded with those is a
journal nobody opens.

### One entry per subject, not per commit

This is the rule that decides how the journal reads. **An entry is a subject,
and it names every commit that moved it** — three commits fixing one thing are
one entry with three hashes in its frontmatter.

Before writing, look at what is already in the directory and at what you just
committed, and ask: *is there an entry this belongs inside?*

- **Same subject, same day → one entry.** A screen reworked in five commits
  over an afternoon is one entry listing five changes, not five entries the
  reader has to reassemble.
- **Same subject, a follow-up the next day → the existing entry**, as long as
  it has not been published long enough to have been read. Add the hash to
  `commits:` and the change to the list; leave `date:` (and therefore the file
  name) on the day the work started.
- **Same day, different subjects → different entries.** A day is not a subject.
- **One commit that is genuinely two subjects → two entries**, each naming the
  same hash. A hash may appear in more than one entry; that is not a problem.

What makes two commits the same subject: a reader coming back in three months
would look them up with the same question.

## The order of operations

The commit hash has to exist before the entry can name it, so:

1. Finish the work and run its tests.
2. **Commit the fix.** Ordinary message, ordinary review.
3. `php artisan change-log:make "What broke, in one sentence" --type=fix`
   — with no `--commit`, it fills in HEAD, which is the commit just made.
   `--type=improvement` for something that got better rather than something
   repaired, and `--tag=<subject> --tag=<part>` to file it (they are lowercased
   for you). **Check first whether an existing entry already covers this
   subject** — if one does, add the hash to its `commits:` instead of running
   this.
4. Write the entry (below).
5. `php artisan change-log:check` — it must pass.
6. **Commit the entry on its own**, message `change-log: <the title>`.

Never `--amend` the fix to fold the entry in: the hash in the frontmatter would
then name a commit that no longer exists.

## What goes in the file

```markdown
---
title: The title, in the past tense, saying what was wrong
date: 2026-09-12
type: fix
tags:
    - console
    - printing
commits:
    - 9a9ab33
---

One paragraph of 30 to 75 words: what the person in front of the screen saw,
then why it happened. No class names in this opening — it is read in a column
24rem wide, and it has to stand up for somebody without the code in front of
them.

## Changes

- What was changed, one bullet per change, in the past tense.
- Name the files and the methods here, where it helps.
- End with the tests added, when there are any.
```

Rules `change-log:check` enforces, so there is no arguing with them:

- File name `YYYY-MM-DD-slug.md`, lowercase, the date matching `date:`.
- `title`, `date`, `type` (`fix` or `improvement`), `tags` and `commits` (short
  hashes) all present.
- Between one and `entries.max_tags` tags, each lowercase and hyphenated
  (accents allowed — a journal names its subjects in its own language).
- The opening paragraph is within `entries.summary_words` — **30 to 75 words**
  by default. It is the summary the list column shows, taken from the body so
  there is nowhere for a second copy to drift.
- At least one `-` bullet after it.
- No `TODO` left in the text.

### The tags

They exist to file an entry, not to describe it. Reuse a tag already in the
journal rather than inventing a synonym — `change-log:check` prints the
vocabulary in use at the end of every run, so look there first.

Two or three tags is the usual shape: the part of the application the work
touched, plus what it is about. Avoid a tag that would only ever hold this one
entry — a sub-subject belongs inside the tag above it.

Rules the checker cannot enforce, and that matter as much:

- **Write in the language of `entries.locale`** (it falls back to the
  application's locale), in the past tense, plainly. The reader is whoever runs
  the installation, not whoever wrote the patch.
- **Say what was actually seen.** "The dashboard came back blank" beats "a
  JavaScript error was thrown".
- **Say why**, in one clause. A journal of symptoms without causes teaches
  nothing.
- Mention what still has to be done in production (a column to fix by hand, a
  cache to clear, a front-end build to deploy) in the last bullet, when there
  is any. This is the part people come back for.

## Public API

```php
use OiLab\OiLaravelChangelogs\Services\ChangeLogRepository;

$changeLogs = app(ChangeLogRepository::class);

$changeLogs->all();                       // Collection<ChangeLogEntry>, newest first
$changeLogs->paginate(20, 1, $url);       // LengthAwarePaginator<ChangeLogRowData>
$changeLogs->find('2026-09-12-the-slug'); // ?ChangeLogEntry
$changeLogs->detail($entry);              // ChangeLogEntryData (html or markdown)
$changeLogs->adjacent($slug);             // ['previous' => ?Row, 'next' => ?Row]
$changeLogs->pageOf($entry, 20);          // the page of the list it sits on
$changeLogs->path();                      // the absolute change log directory
```

`OiLab\OiLaravelChangelogs\OiLaravelChangelogs` answers everything read from
the configuration: `path()`, `componentsPath()`, `pagesPath()`,
`componentsAlias()`, `component()`, `routeName()`, `perPage()`, `locale()`,
`summaryWords()`, `maxTags()`, `rendersOnServer()`.

## Commands

```bash
php artisan change-log:install                    # config, directory, React screen
php artisan change-log:make "What broke"          # open an entry, HEAD already in it
    --type=fix|improvement --tag=<subject> --commit=<hash> --date=YYYY-MM-DD --force
php artisan change-log:check                      # hold every entry to the format
php artisan vendor:publish --tag=oi-laravel-changelogs-config
```

Put `change-log:check` in the test suite so a malformed entry fails the build
rather than quietly vanishing from the screen:

```php
it('keeps every change log entry well formed', function () {
    $this->withoutMockingConsoleOutput();

    expect($this->artisan('change-log:check'))->toBe(0);
});
```

## Configuration

`config/oi-laravel-changelogs.php`:

| Key | Default | What it decides |
| --- | --- | --- |
| `changelog_path` | `resources/markdown/change-logs` | where the entries live |
| `components_path` | `resources/js/components/change-logs` | where the published components live, and the `@/` alias they import each other by |
| `pages_path` | `resources/js/pages/change-logs` | where the page lives, **and** the Inertia component the controller renders |
| `route.enabled` | `env('OI_CHANGELOGS_ROUTES', true)` | set to false to declare your own routes |
| `route.prefix` | `change-logs` | the url |
| `route.name` | `change-logs.` | the route-name prefix |
| `route.middleware` | `['web']` | who may read the journal |
| `rendering.markdown_engine` | `server` | `server` = html built by Laravel, `client` = markdown rendered by react-markdown |
| `rendering.ssr` | `false` | whether the host renders Inertia with SSR |
| `rendering.typeset` | `false` | use Shadcn's `typeset` class instead of `prose` |
| `entries.locale` | `null` | the language entries are written in (null = app locale) |
| `entries.summary_words` | `['min' => 30, 'max' => 75]` | the bounds on the opening paragraph |
| `entries.max_tags` | `5` | how many subjects one entry may be filed under |
| `per_page` | `20` | entries per page of the list |
| `cache.store` / `cache.ttl` | `null` / `3600` | where the parse is kept, and for how long |

## Host Integration Checklist

1. `composer require oi-lab/oi-laravel-changelogs`
2. `php artisan change-log:install` — answers who may read it, where the
   markdown is converted, and writes the screen.
3. Point the published page at the application's own layout
   (`ChangeLogsIndex.layout`), or keep the neutral one.
4. Add a link to `route('change-logs.index')` wherever the application lists
   its own screens.
5. `npm run build`.
6. Add `change-log:check` to the test suite.

## Updating the AI Skill

After updating this package, re-install the skill files:

```bash
php artisan oi:skills oilab-laravel-changelogs --project
```
