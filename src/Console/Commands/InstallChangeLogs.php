<?php

namespace OiLab\OiLaravelChangelogs\Console\Commands;

use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use OiLab\OiLaravelChangelogs\OiLaravelChangelogs;

/**
 * Put the journal in place: the config, the directory, and the screen.
 *
 * Everything it writes is yours afterwards. The React files are stubs, not a
 * dependency — the package renders nothing itself, so a host free to rewrite
 * the screen is the whole point of installing it rather than importing it.
 */
class InstallChangeLogs extends Command
{
    private const DEFAULT_COMPONENTS_ALIAS = '@/components/change-logs';

    protected $signature = 'change-log:install {--force : Overwrite existing files}';

    protected $description = 'Install the change log configuration, directory and React screen';

    /**
     * The npm packages the client-side renderer needs, and nothing else: the
     * server-side one needs none.
     *
     * @var array<string, string>
     */
    private array $clientNpmPackages = [
        'react-markdown' => '^9.0.0',
        'remark-gfm' => '^4.0.0',
    ];

    /**
     * The Shadcn UI components the published screen draws with.
     *
     * @var list<string>
     */
    private array $shadcnComponents = ['badge', 'button', 'empty', 'item', 'skeleton'];

    private ?string $packageManager = null;

    public function handle(): int
    {
        $this->components->info('OI Lab Change Logs — installation');

        $force = (bool) $this->option('force');

        $steps = [
            'config' => 'Configuration file',
            'entries' => 'Change log directory',
            'components' => 'React screen (components, layout, page)',
            'shadcn' => 'Shadcn UI components',
        ];

        $selected = array_filter(
            $steps,
            fn (string $key): bool => $this->shouldInstall($key, $force),
            ARRAY_FILTER_USE_KEY,
        );

        if ($selected === []) {
            $this->components->info('Everything is already installed. Pass --force to reinstall.');

            return static::SUCCESS;
        }

        $this->components->bulletList($selected);

        if (! $this->confirm('Install these?', true)) {
            $this->components->warn('Installation cancelled.');

            return static::FAILURE;
        }

        if (isset($selected['config'])) {
            $this->publishConfig($force);
            $this->configureRouteAccess();
            $this->configureRendering();
        }

        if (isset($selected['entries'])) {
            $this->createChangeLogDirectory($force);
        }

        if (isset($selected['components'])) {
            $this->installReactFiles($force);
            $this->checkNpmPackages();
        }

        if (isset($selected['shadcn'])) {
            $this->installShadcnComponents();
        }

        $this->newLine();
        $this->components->info('Done.');
        $this->components->bulletList([
            'Write an entry: php artisan change-log:make "What broke" --type=fix --tag=<subject>',
            'Check the format: php artisan change-log:check',
            'Read the journal at /'.trim((string) config('oi-laravel-changelogs.route.prefix', 'change-logs'), '/'),
            'Install the AI skill in your app: php artisan oi:skills oilab-laravel-changelogs --project',
        ]);

        return static::SUCCESS;
    }

    private function shouldInstall(string $step, bool $force): bool
    {
        if ($force) {
            return true;
        }

        return match ($step) {
            'config' => ! File::exists(config_path('oi-laravel-changelogs.php')),
            'entries' => ! File::isDirectory(OiLaravelChangelogs::path()),
            'components' => ! File::exists(base_path(OiLaravelChangelogs::pagesPath().'/index.tsx')),
            'shadcn' => ! $this->areShadcnComponentsInstalled(),
            default => false,
        };
    }

    private function publishConfig(bool $force): void
    {
        $this->call('vendor:publish', [
            '--tag' => 'oi-laravel-changelogs-config',
            '--force' => $force,
        ]);
    }

    /**
     * Who may read the journal.
     *
     * Asked rather than assumed: an entry names the files it touched and the
     * commits it came from, which is not something every installation wants on
     * a public url.
     */
    private function configureRouteAccess(): void
    {
        $configPath = config_path('oi-laravel-changelogs.php');

        if (! File::exists($configPath)) {
            return;
        }

        $public = 'Public — anyone can read the journal';
        $auth = 'Authenticated users only (adds the "auth" middleware)';
        $custom = 'Restricted by a custom middleware';

        $choice = $this->choice('Who should be able to read the change logs?', [$public, $auth, $custom], $auth);

        $middleware = ['web'];

        if ($choice === $auth) {
            $middleware[] = 'auth';
        } elseif ($choice === $custom) {
            foreach (explode(',', (string) $this->ask('Middleware name(s), comma separated', 'auth')) as $name) {
                $name = trim($name);

                if ($name !== '' && ! in_array($name, $middleware, true)) {
                    $middleware[] = $name;
                }
            }
        }

        $this->replaceInConfig(
            $configPath,
            "/'middleware'\s*=>\s*\[[^\]]*\],/",
            "'middleware' => ['".implode("', '", $middleware)."'],",
            'route.middleware',
        );

        config()->set('oi-laravel-changelogs.route.middleware', $middleware);

        $this->components->info('Route middleware: ['.implode(', ', $middleware).']');
    }

    private function configureRendering(): void
    {
        $configPath = config_path('oi-laravel-changelogs.php');

        if (! File::exists($configPath)) {
            return;
        }

        $server = 'Server-side — Laravel converts the markdown to HTML (no npm dependency)';
        $client = 'Client-side — the browser converts it (react-markdown + remark-gfm)';

        $engine = $this->choice('Where should the markdown be converted?', [$server, $client], $server) === $client
            ? 'client'
            : 'server';

        $this->replaceConfigValue($configPath, 'markdown_engine', "'{$engine}'");
        $this->components->info("Markdown engine: {$engine}");

        $ssr = $this->confirm('Does your application render Inertia with SSR (resources/js/ssr.tsx)?', false);
        $this->replaceConfigValue($configPath, 'ssr', $ssr ? 'true' : 'false');

        if ($ssr && ! File::exists(resource_path('js/ssr.tsx'))) {
            $this->components->warn('resources/js/ssr.tsx was not found. Set up Inertia SSR before relying on it.');
        }

        $typeset = $this->confirm('Apply Shadcn UI\'s "typeset" typography class to the entry content?', false);
        $this->replaceConfigValue($configPath, 'typeset', $typeset ? 'true' : 'false');

        if ($typeset && ! File::exists(resource_path('css/typeset.css'))) {
            $this->components->warn('resources/css/typeset.css was not found — the "typeset" class will resolve to nothing.');
        }
    }

    /**
     * Write a rendering choice to the published config, and to the
     * configuration this run is still reading.
     *
     * The steps that follow — which component to install, which import to
     * leave out — read the answers given a moment ago, and a file on disk is
     * not what config() reads.
     */
    private function replaceConfigValue(string $configPath, string $key, string $value): void
    {
        $this->replaceInConfig(
            $configPath,
            "/'".preg_quote($key, '/')."'\s*=>\s*(?:'[^']*'|true|false|null),/",
            "'{$key}' => {$value},",
            $key,
        );

        config()->set(
            'oi-laravel-changelogs.rendering.'.$key,
            match ($value) {
                'true' => true,
                'false' => false,
                default => trim($value, "'"),
            },
        );
    }

    private function replaceInConfig(string $configPath, string $pattern, string $replacement, string $label): void
    {
        $updated = preg_replace($pattern, $replacement, File::get($configPath), 1, $count);

        if ($count > 0 && $updated !== null) {
            File::put($configPath, $updated);

            return;
        }

        $this->components->warn("Could not set '{$label}' automatically — edit config/oi-laravel-changelogs.php by hand.");
    }

    /**
     * The directory, with one entry in it showing the format.
     */
    private function createChangeLogDirectory(bool $force): void
    {
        $path = OiLaravelChangelogs::path();

        File::ensureDirectoryExists($path);

        $date = CarbonImmutable::today()->toDateString();
        $file = $path.'/'.$date.'-an-example-entry.md';

        if (File::exists($file) && ! $force) {
            $this->components->warn('The example entry already exists.');
        } else {
            File::put($file, str_replace(
                '__DATE__',
                $date,
                File::get(__DIR__.'/../../../stubs/change-logs/example.md'),
            ));
        }

        $this->components->info('Change log directory: '.OiLaravelChangelogs::relativePath());
    }

    /**
     * The screen: the components, the two helpers, the layout and the page.
     */
    private function installReactFiles(bool $force): void
    {
        $componentsPath = OiLaravelChangelogs::componentsPath();
        $pagesPath = OiLaravelChangelogs::pagesPath();

        foreach ([$componentsPath, $pagesPath] as $path) {
            if (! str_starts_with($path, 'resources/js/')) {
                $this->components->warn("{$path} sits outside resources/js/ — the \"@/\" imports in the published files will need fixing by hand.");
            }
        }

        $stubs = __DIR__.'/../../../stubs/js';
        $onServer = OiLaravelChangelogs::rendersOnServer();

        $groups = [
            [
                'source' => $stubs.'/components',
                'target' => $componentsPath,
                'files' => array_values(array_filter([
                    'types.ts',
                    'change-log-row.tsx',
                    'change-log-detail.tsx',
                    'change-log-pagination.tsx',
                    'change-log-html-content.tsx',
                    $onServer ? null : 'change-log-markdown-content.tsx',
                ])),
            ],
            [
                'source' => $stubs.'/lib',
                'target' => 'resources/js/lib',
                'files' => ['change-log-typography.ts', 'change-log-date.ts'],
            ],
            [
                'source' => $stubs.'/layouts',
                'target' => 'resources/js/layouts',
                'files' => ['change-logs-layout.tsx'],
            ],
            [
                'source' => $stubs.'/pages',
                'target' => $pagesPath,
                'files' => ['index.tsx'],
            ],
        ];

        foreach ($groups as $group) {
            File::ensureDirectoryExists(base_path($group['target']));

            foreach ($group['files'] as $file) {
                $target = base_path($group['target'].'/'.$file);
                $label = $group['target'].'/'.$file;

                if (File::exists($target) && ! $force && ! $this->confirm("{$label} already exists. Overwrite?", false)) {
                    $this->line("  <fg=gray>skipped</> {$label}");

                    continue;
                }

                File::put($target, $this->stubContents($group['source'].'/'.$file));
                $this->line("  <fg=green>installed</> {$label}");
            }
        }
    }

    /**
     * A stub, rewritten for this installation.
     *
     * The order matters: the engine and typography edits are written against
     * the default alias, so they have to land before the alias itself is
     * rewritten.
     */
    private function stubContents(string $source): string
    {
        $contents = File::get($source);

        foreach ($this->replacements() as $search => $replacement) {
            $contents = str_replace($search, $replacement, $contents);
        }

        $alias = OiLaravelChangelogs::componentsAlias();

        if ($alias !== self::DEFAULT_COMPONENTS_ALIAS) {
            $contents = str_replace(
                self::DEFAULT_COMPONENTS_ALIAS.'/',
                rtrim($alias, '/').'/',
                $contents,
            );
        }

        return $contents;
    }

    /**
     * What the configuration changes in the published files.
     *
     * When the markdown is converted on the server, the client renderer is
     * neither installed nor imported: an unused import of react-markdown is a
     * dependency the host would have to install to build.
     *
     * @return array<string, string>
     */
    private function replacements(): array
    {
        $replacements = [];

        if (config('oi-laravel-changelogs.rendering.typeset', false)) {
            $replacements["export const CHANGE_LOG_TYPOGRAPHY_CLASS = 'prose dark:prose-invert max-w-none';"]
                = "export const CHANGE_LOG_TYPOGRAPHY_CLASS = 'typeset';";
        }

        if (OiLaravelChangelogs::rendersOnServer()) {
            $replacements["import ChangeLogMarkdownContent from '@/components/change-logs/change-log-markdown-content';\n"] = '';

            $replacements[
                "                    {entry.html !== null ? (\n"
                ."                        <ChangeLogHtmlContent html={entry.html} />\n"
                ."                    ) : (\n"
                ."                        <ChangeLogMarkdownContent markdown={entry.markdown ?? ''} />\n"
                .'                    )}'
            ] = '                    <ChangeLogHtmlContent html={entry.html ?? \'\'} />';
        }

        return $replacements;
    }

    /**
     * The npm packages the chosen engine needs.
     */
    private function checkNpmPackages(): void
    {
        if (OiLaravelChangelogs::rendersOnServer()) {
            return;
        }

        $packageJsonPath = base_path('package.json');

        if (! File::exists($packageJsonPath)) {
            $this->components->warn('No package.json found — skipping the npm dependency check.');

            return;
        }

        /** @var array<string, mixed> $packageJson */
        $packageJson = json_decode(File::get($packageJsonPath), true) ?: [];

        $installed = array_merge(
            (array) ($packageJson['dependencies'] ?? []),
            (array) ($packageJson['devDependencies'] ?? []),
        );

        $missing = array_diff_key($this->clientNpmPackages, $installed);

        if ($missing === []) {
            return;
        }

        $packages = implode(' ', array_map(
            static fn (string $version, string $package): string => $package.'@'.$version,
            $missing,
            array_keys($missing),
        ));

        $command = $this->installCommand($packages);

        if (! $this->confirm("The client-side renderer needs: {$packages}. Install them now?", true)) {
            $this->components->warn('Install them later with: '.$command);

            return;
        }

        $this->line("Running: {$command}");
        passthru($command, $exitCode);

        if ($exitCode !== 0) {
            $this->components->error('npm install failed. Run it by hand: '.$command);
        }
    }

    private function areShadcnComponentsInstalled(): bool
    {
        foreach ($this->shadcnComponents as $component) {
            if (! File::exists(resource_path("js/components/ui/{$component}.tsx"))) {
                return false;
            }
        }

        return true;
    }

    private function installShadcnComponents(): void
    {
        $missing = array_values(array_filter(
            $this->shadcnComponents,
            static fn (string $component): bool => ! File::exists(resource_path("js/components/ui/{$component}.tsx")),
        ));

        if ($missing === []) {
            return;
        }

        $command = $this->dlxCommand('shadcn@latest add '.implode(' ', $missing).' --yes');

        if (! $this->confirm('The screen draws with these Shadcn UI components: '.implode(', ', $missing).'. Add them now?', true)) {
            $this->components->warn('Add them later with: '.$command);

            return;
        }

        $this->line("Running: {$command}");
        passthru($command, $exitCode);

        if ($exitCode !== 0) {
            $this->components->error('The shadcn CLI failed. Run it by hand: '.$command);
        }
    }

    private function installCommand(string $packages): string
    {
        return match ($this->packageManager()) {
            'pnpm' => "pnpm add {$packages}",
            'yarn' => "yarn add {$packages}",
            default => "npm install {$packages}",
        };
    }

    private function dlxCommand(string $command): string
    {
        return match ($this->packageManager()) {
            'pnpm' => "pnpm dlx {$command}",
            'yarn' => "yarn dlx {$command}",
            default => "npx {$command}",
        };
    }

    private function packageManager(): string
    {
        return $this->packageManager ??= match (true) {
            File::exists(base_path('pnpm-lock.yaml')) => 'pnpm',
            File::exists(base_path('yarn.lock')) => 'yarn',
            default => 'npm',
        };
    }
}
