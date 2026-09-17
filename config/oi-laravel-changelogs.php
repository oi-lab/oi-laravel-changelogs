<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Change Log Path
    |--------------------------------------------------------------------------
    |
    | Where the entries of the journal are kept: one markdown file per entry,
    | named "YYYY-MM-DD-slug.md". The path is relative to the base path of the
    | application, so a test can point it at its own fixtures with a single
    | config() call.
    |
    | Nothing is ever written here at runtime. An entry is authored with the
    | fix it describes, reviewed with it, and deployed with it — the screen
    | reads this directory and never adds to it.
    |
    */
    'changelog_path' => 'resources/markdown/change-logs',

    /*
    |--------------------------------------------------------------------------
    | Components Path
    |--------------------------------------------------------------------------
    |
    | The directory (relative to the base path) where `change-log:install`
    | writes the published React components. Keep it under "resources/js/" so
    | the components can keep using the "@/" import alias.
    |
    */
    'components_path' => 'resources/js/components/change-logs',

    /*
    |--------------------------------------------------------------------------
    | Pages Path
    |--------------------------------------------------------------------------
    |
    | Where the published Inertia page lands. It also decides the component
    | name the controller renders: "resources/js/pages/console/change-logs"
    | is rendered as "console/change-logs/index", so moving the page into
    | your console space is a one-line config change and no code edit.
    |
    */
    'pages_path' => 'resources/js/pages/change-logs',

    /*
    |--------------------------------------------------------------------------
    | Route Configuration
    |--------------------------------------------------------------------------
    |
    | The two GET routes the package registers: the journal, and one entry.
    | There is no writing endpoint and none is coming — a console able to add
    | an entry would be a second journal nobody diffs.
    |
    | Set "enabled" to false to register your own routes against the package's
    | controller instead.
    |
    */
    'route' => [
        'enabled' => env('OI_CHANGELOGS_ROUTES', true),
        'prefix' => 'change-logs',
        'name' => 'change-logs.',
        'middleware' => ['web'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rendering Configuration
    |--------------------------------------------------------------------------
    |
    | Configured during installation.
    |
    | - "markdown_engine": "server" converts the markdown to HTML in Laravel
    |   (via league/commonmark) and the page draws it with
    |   ChangeLogHtmlContent. "client" sends the raw markdown and renders it
    |   with ChangeLogMarkdownContent (react-markdown + remark-gfm), which
    |   costs two npm packages and a little more work in the browser.
    | - "ssr": whether the host application renders the Inertia app with
    |   server-side rendering (resources/js/ssr.tsx).
    | - "typeset": apply Shadcn UI's "typeset" typography class to the
    |   rendered content container instead of "typography". Requires
    |   resources/css/typeset.css to exist.
    |
    */
    'rendering' => [
        'markdown_engine' => 'server',
        'ssr' => false,
        'typeset' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Entries
    |--------------------------------------------------------------------------
    |
    | The format `change-log:check` holds every entry to.
    |
    | - "locale": the language the entries are written in. Null falls back to
    |   the application's own locale. The AI skill reads it to know which
    |   language to write an entry in; nothing else depends on it.
    | - "summary_words": the bounds the opening paragraph is held to. Short
    |   enough to be read in the list column, long enough to say what broke.
    | - "max_tags": how many subjects one entry may be filed under. Past that,
    |   somebody is describing the entry rather than filing it.
    |
    */
    'entries' => [
        'locale' => null,
        'summary_words' => ['min' => 30, 'max' => 75],
        'max_tags' => 5,
    ],

    /*
    |--------------------------------------------------------------------------
    | List
    |--------------------------------------------------------------------------
    |
    | How many entries one page of the list carries.
    |
    */
    'per_page' => 20,

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    |
    | The whole directory is parsed to draw one page of the list, so the parse
    | is cached under a fingerprint of the directory itself: a file added,
    | edited or removed changes the fingerprint and the next read rebuilds.
    | Nothing has to be regenerated by hand.
    |
    | "ttl" only bounds how long a stale parse could survive a clock that went
    | backwards; set "store" to null to use the application's default store.
    |
    */
    'cache' => [
        'store' => null,
        'ttl' => 3600,
    ],
];
