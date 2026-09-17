---
title: Writing an entry
description: The order of operations, the format, and why one entry is one subject.
section: usage
order: 2
---

# Writing an entry

## When one is owed

Write an entry when a commit **changes what somebody using the application
experiences**: a bug fixed, a wrong behaviour corrected, an interaction made
better, a screen that now shows something it did not.

Do not write one for a refactor with no visible effect, a dependency bump, a
test-only change, a comment or a typo. A journal padded with those is a journal
nobody opens.

## One entry per subject, not per commit

This is the rule that decides how the journal reads. **An entry is a subject,
and it names every commit that moved it** — three commits fixing one thing are
one entry with three hashes in its frontmatter.

- Same subject, same day, five commits: one entry listing five changes.
- Same subject, a follow-up the next day: the existing entry. Add the hash to
  `commits:` and the change to the list; leave `date:` on the day the work
  started, so the file keeps its name.
- Same day, different subjects: different entries. A day is not a subject.
- One commit that is genuinely two subjects: two entries naming the same hash.
  A hash may appear in more than one entry.

What makes two commits the same subject: a reader coming back in three months
would look them up with the same question.

## The order of operations

The commit hash has to exist before the entry can name it.

1. Finish the work and run its tests.
2. **Commit the fix.** Ordinary message, ordinary review.
3. Open the entry:

   ```bash
   php artisan change-log:make "The dashboard came back blank" \
       --type=fix \
       --tag=console --tag=teams
   ```

   With no `--commit`, it fills in HEAD — the commit you just made. Check first
   whether an existing entry already covers the subject; if one does, add the
   hash to its `commits:` instead of running this.
4. Write the entry.
5. `php artisan change-log:check` — it must pass.
6. **Commit the entry on its own**, message `change-log: <the title>`.

Never `--amend` the fix to fold the entry in: the hash in the frontmatter would
then name a commit that no longer exists.

### Options

| Option | What it does |
| --- | --- |
| `--type=` | `fix` or `improvement`. There is no third case. |
| `--tag=` | Repeatable. Lowercased for you. |
| `--commit=` | Repeatable. Defaults to HEAD; empty when git cannot be read. |
| `--date=` | The day it is published. Defaults to today, and names the file. |
| `--force` | Overwrite an entry already carrying that name. |

## The file

```markdown
---
title: The dashboard came back blank
date: 2026-09-12
type: fix
tags:
    - console
    - teams
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

The opening paragraph is not a frontmatter key. It is taken from the body, so
there is one place to write it and no way for two copies to disagree.

## The tags

They exist to file an entry, not to describe it. Reuse a tag already in the
journal rather than inventing a synonym — `change-log:check` prints the
vocabulary in use at the end of every run, so look there first.

Two or three is the usual shape: the part of the application the work touched,
plus what it is about. Avoid a tag that would only ever hold one entry — a
sub-subject belongs inside the tag above it.

## What the checker cannot enforce

- **Write in the language of `entries.locale`**, in the past tense, plainly.
  The reader is whoever runs the installation, not whoever wrote the patch.
- **Say what was actually seen.** "The dashboard came back blank" beats "a
  JavaScript error was thrown".
- **Say why**, in one clause. A journal of symptoms without causes teaches
  nothing.
- Mention what still has to be done in production — a column to fix by hand, a
  cache to clear, a front-end build to deploy — in the last bullet. This is the
  part people come back for.
