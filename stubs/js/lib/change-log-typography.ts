/**
 * Class applied to the rendered content container by both
 * ChangeLogHtmlContent and ChangeLogMarkdownContent.
 *
 * `php artisan change-log:install` sets this to "typeset" instead of
 * "prose" when you opt into Shadcn UI's typography plugin (which requires
 * resources/css/typeset.css to exist). Replace it with whatever your
 * application uses to style long-form text — it is the one place to do it.
 */
export const CHANGE_LOG_TYPOGRAPHY_CLASS = 'prose dark:prose-invert max-w-none';
