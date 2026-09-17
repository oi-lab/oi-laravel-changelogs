---
title: The list of workshops came back empty for everyone but administrators
date: __DATE__
type: fix
tags:
    - workshops
    - teams
commits: []
---

The list of workshops came back empty for anyone who was not an administrator, and the page said nothing about why — no message, no empty state, just a blank column under the filters. The query scoped the results to the current team before the team had been resolved from the session.

## Changes

- Resolved the current team before the scope is applied, so the filter has something to filter on.
- Drew an empty state on the list, with the reason, instead of an empty column.
- Added a feature test covering a member of one team and a member of none.

This file was written by `php artisan change-log:install` as an example of the format. Delete it, then write your own with `php artisan change-log:make`.
