# Change Logs

Use the `oi-laravel-changelogs` package to keep the journal of what was fixed and what was
improved in this application: one markdown file per entry under `changelog_path`
(default `resources/markdown/change-logs/`), read on the screen the package installs at
`/change-logs`. An entry is a subject, not a commit — three commits fixing one thing are one
entry naming three hashes. Open one with `php artisan change-log:make "What broke" --type=fix
--tag=<subject>` (it fills in HEAD), then hold it to the format with `php artisan
change-log:check`, which must pass before the entry is committed.

- IMPORTANT: Activate `oilab-laravel-changelogs` whenever a bug has just been fixed, a behaviour
  corrected or a feature improved — right after committing the work, before considering the task
  done. Also activate it when asked to publish, update, check or reformat a change log entry, when
  working under the change log directory, or when the words change log, changelog or release note
  come up.
