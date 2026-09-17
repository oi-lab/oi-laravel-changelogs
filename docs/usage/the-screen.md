---
title: The published screen
description: What the installer wrote, and how to make it yours.
section: usage
order: 5
---

# The published screen

The installer writes these, and you own them afterwards:

```
resources/js/components/change-logs/
├── types.ts                        # ChangeLogRow, ChangeLogEntry, ChangeLogAnswer
├── change-log-row.tsx              # one row of the list
├── change-log-detail.tsx           # the entry, and the two buttons that walk the list
├── change-log-pagination.tsx       # previous / next / counter
├── change-log-html-content.tsx     # rendering.markdown_engine = "server"
└── change-log-markdown-content.tsx # rendering.markdown_engine = "client"
resources/js/lib/change-log-typography.ts
resources/js/lib/change-log-date.ts
resources/js/layouts/change-logs-layout.tsx
resources/js/pages/change-logs/index.tsx
```

They are deliberately plain: Shadcn UI's `badge`, `button`, `empty`, `item` and
`skeleton`, a `cn()` call where a class has to be conditional, and nothing
else. There is no design system of ours in them to unpick.

## Only one renderer is installed

When the markdown is converted on the server, `change-log-markdown-content.tsx`
is not written and its import is taken out of `change-log-detail.tsx`. An
unused import of `react-markdown` is a dependency you would have to install to
build, for a component that would never render.

Switching engines afterwards is `rendering.markdown_engine` plus
`php artisan change-log:install --force`.

## The layout

The page names the neutral layout at the bottom of the file:

```tsx
ChangeLogsIndex.layout = (page: ReactNode) => (
    <ChangeLogsLayout>{page}</ChangeLogsLayout>
);
```

Point it at your application's own, keeping the two constraints the neutral one
carries:

```tsx
<div className="flex h-svh min-h-0 flex-col">
```

`h-svh` and not `min-h-svh`. An application shell is usually a floor: a page
taller than the window simply makes the document scroll, and a pane asked to
scroll inside a floor never does — it has no height to scroll within, so it
grows and takes the document with it. The list and the entry each scroll on
their own, and they hang from this ceiling.

## Moving the screen into a console space

```php
'pages_path' => 'resources/js/pages/console/change-logs',
```

The pages path is also the Inertia component name: the controller renders the
path with `resources/js/pages/` cut off it, so this is the only change needed.
Re-run the installer, or move the file yourself.

## Typography

Both content components apply `CHANGE_LOG_TYPOGRAPHY_CLASS` from
`resources/js/lib/change-log-typography.ts`. It is `prose dark:prose-invert
max-w-none` by default, or `typeset` if you said yes to Shadcn's typography
plugin at install time. It is one constant in one file: replace it with
whatever your application uses to style long-form text.

## TypeScript

The published `types.ts` mirrors the two Data classes by hand. If your
application generates its interfaces, replace it:

```bash
php artisan oi:gen-ts
```
