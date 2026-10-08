# Changelog

All notable changes to `oi-lab/oi-laravel-changelogs` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Fixed

- `change-log:make` quotes the commit hashes it writes: an unquoted short hash
  made only of digits and one `e` (`677e661`) was read back by YAML as a float
  in scientific notation, and one made only of digits as an integer, so the
  entry named a commit that does not exist.

## [1.0.0] - 2026-09-17

### Added

- Markdown-backed change log journal: one file per entry under the configured
  `changelog_path`, read through `ChangeLogRepository` with no table and no
  migration.
- `ChangeLogRepository` caching its parse under a fingerprint of the directory,
  so a file added, edited or removed is picked up on the next read with nothing
  to regenerate by hand.
- `spatie/laravel-data` objects on the wire: `ChangeLogRowData` for the list,
  `ChangeLogEntryData` for the entry, `ChangeLogAnswerData` for the fetch the
  screen makes.
- `ChangeLogType` enum (`fix`, `improvement`) with translated labels, shipped in
  English and French.
- `ChangeLogController` and two config-gated routes: `change-logs.index` and
  `change-logs.show`, the second answering json to a fetch and the whole screen
  to a visit.
- `change-log:install` — an installer that publishes the config, asks who may
  read the journal and where the markdown is converted, creates the directory
  with an example entry, writes the React screen and offers the Shadcn UI
  components it draws with.
- `change-log:make` — opens an entry with its frontmatter filled in, HEAD
  included, placeholders written through the translator.
- `change-log:check` — holds every entry to the format and prints the tag
  vocabulary in use.
- Neutral Inertia/React stubs (page, layout, row, detail, pagination, both
  content renderers, types and two helpers), rewritten at install time for the
  configured component alias, markdown engine and typography class.
- Configurable `changelog_path`, `components_path`, `pages_path`, `route`,
  `rendering`, `entries`, `per_page` and `cache`; the pages path also decides
  the Inertia component the controller renders.
- `oilab-laravel-changelogs` AI skill, documentation tree and CI workflow.
- PHP 8.2–8.4, Laravel 11–13, 88 tests.
