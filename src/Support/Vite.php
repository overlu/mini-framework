<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Support;

use Exception;
use Mini\Contracts\Support\Htmlable;
use Mini\Exception\ViteManifestNotFoundException;
use Mini\Support\Traits\Macroable;
use RuntimeException;

class Vite implements Htmlable
{
    use Macroable;

    /**
     * The Content Security Policy nonce to apply to all generated tags.
     *
     * @var string|null
     */
    protected ?string $nonce = null;

    /**
     * The key to check for integrity hashes within the manifest.
     *
     * @var string|false
     */
    protected string|false $integrityKey = 'integrity';

    /**
     * The configured entry points.
     *
     * @var array
     */
    protected array $entryPoints = [];

    /**
     * The path to the "hot" file.
     *
     * @var string|null
     */
    protected ?string $hotFile = null;

    /**
     * The path to the build directory.
     *
     * @var string
     */
    protected string $buildDirectory = 'build';

    /**
     * The name of the manifest file.
     *
     * @var string
     */
    protected string $manifestFilename = 'manifest.json';

    /**
     * The script tag attributes resolvers.
     *
     * @var array
     */
    protected array $scriptTagAttributesResolvers = [];

    /**
     * The style tag attributes resolvers.
     *
     * @var array
     */
    protected array $styleTagAttributesResolvers = [];

    /**
     * The preload tag attributes resolvers.
     *
     * @var array
     */
    protected array $preloadTagAttributesResolvers = [];

    /**
     * The preloaded assets.
     *
     * @var array
     */
    protected array $preloadedAssets = [];

    /**
     * The cached manifest files.
     *
     * @var array
     */
    protected static array $manifests = [];

    /**
     * Get the preloaded assets.
     *
     * @return array
     */
    public function preloadedAssets(): array
    {
        return $this->preloadedAssets;
    }

    /**
     * Get the Content Security Policy nonce applied to all generated tags.
     *
     * @return string|null
     */
    public function cspNonce(): ?string
    {
        return $this->nonce;
    }

    /**
     * Generate or set a Content Security Policy nonce to apply to all generated tags.
     *
     * @param string|null $nonce
     * @return string
     * @throws Exception
     */
    public function useCspNonce(string $nonce = null): string
    {
        return $this->nonce = $nonce ?? Str::random(40);
    }

    /**
     * Use the given key to detect integrity hashes in the manifest.
     *
     * @param bool|string $key
     * @return $this
     */
    public function useIntegrityKey(bool|string $key): self
    {
        $this->integrityKey = $key;

        return $this;
    }

    /**
     * Set the Vite entry points.
     *
     * @param array $entryPoints
     * @return $this
     */
    public function withEntryPoints(array $entryPoints): self
    {
        $this->entryPoints = $entryPoints;

        return $this;
    }

    /**
     * Set the filename for the manifest file.
     *
     * @param string $filename
     * @return $this
     */
    public function useManifestFilename(string $filename): self
    {
        $this->manifestFilename = $filename;

        return $this;
    }

    /**
     * Get the Vite "hot" file path.
     *
     * @return string
     */
    public function hotFile(): string
    {
        return $this->hotFile ?? public_path('/hot');
    }

    /**
     * Set the Vite "hot" file path.
     *
     * @param string $path
     * @return $this
     */
    public function useHotFile(string $path): self
    {
        $this->hotFile = $path;

        return $this;
    }

    /**
     * Set the Vite build directory.
     *
     * @param string $path
     * @return $this
     */
    public function useBuildDirectory(string $path): self
    {
        $this->buildDirectory = $path;

        return $this;
    }

    /**
     * Use the given callback to resolve attributes for script tags.
     *
     * @param  (callable(string, string, ?array, ?array): array)|array  $attributes
     * @return $this
     */
    public function useScriptTagAttributes($attributes): self
    {
        if (!is_callable($attributes)) {
            $attributes = fn() => $attributes;
        }

        $this->scriptTagAttributesResolvers[] = $attributes;

        return $this;
    }

    /**
     * Use the given callback to resolve attributes for style tags.
     *
     * @param  (callable(string, string, ?array, ?array): array)|array  $attributes
     * @return $this
     */
    public function useStyleTagAttributes($attributes): self
    {
        if (!is_callable($attributes)) {
            $attributes = fn() => $attributes;
        }

        $this->styleTagAttributesResolvers[] = $attributes;

        return $this;
    }

    /**
     * Use the given callback to resolve attributes for preload tags.
     *
     * @param  (callable(string, string, ?array, ?array): (array|false))|array|false  $attributes
     * @return $this
     */
    public function usePreloadTagAttributes($attributes): self
    {
        if (!is_callable($attributes)) {
            $attributes = fn() => $attributes;
        }

        $this->preloadTagAttributesResolvers[] = $attributes;

        return $this;
    }

    /**
     * Generate Vite tags for an entrypoint.
     *
     * @param string|string[] $entrypoints
     * @param string|null $buildDirectory
     * @return \Mini\Support\HtmlString
     *
     * @throws Exception
     */
    public function __invoke(array|string $entrypoints, string $buildDirectory = null): HtmlString
    {
        $entrypoints = collect($entrypoints);
        $buildDirectory ??= $this->buildDirectory;

        if ($this->isRunningHot()) {
            return new HtmlString(
                $entrypoints
                    ->prepend('@vite/client')
                    ->map(fn($entrypoint) => $this->makeTagForChunk($entrypoint, $this->hotAsset($entrypoint), null, null))
                    ->join('')
            );
        }

        $manifest = $this->manifest($buildDirectory);

        $tags = collect();
        $preloads = collect();

        foreach ($entrypoints as $entrypoint) {
            $chunk = $this->chunk($manifest, $entrypoint);

            $preloads->push([
                $chunk['src'],
                $this->assetPath("{$buildDirectory}/{$chunk['file']}"),
                $chunk,
                $manifest,
            ]);

            foreach ($chunk['imports'] ?? [] as $import) {
                $preloads->push([
                    $import,
                    $this->assetPath("{$buildDirectory}/{$manifest[$import]['file']}"),
                    $manifest[$import],
                    $manifest,
                ]);

                foreach ($manifest[$import]['css'] ?? [] as $css) {
                    $partialManifest = Collection::make($manifest)->where('file', $css);

                    $preloads->push([
                        $partialManifest->keys()->first(),
                        $this->assetPath("{$buildDirectory}/{$css}"),
                        $partialManifest->first(),
                        $manifest,
                    ]);

                    $tags->push($this->makeTagForChunk(
                        $partialManifest->keys()->first(),
                        $this->assetPath("{$buildDirectory}/{$css}"),
                        $partialManifest->first(),
                        $manifest
                    ));
                }
            }

            $tags->push($this->makeTagForChunk(
                $entrypoint,
                $this->assetPath("{$buildDirectory}/{$chunk['file']}"),
                $chunk,
                $manifest
            ));

            foreach ($chunk['css'] ?? [] as $css) {
                $partialManifest = Collection::make($manifest)->where('file', $css);

                $preloads->push([
                    $partialManifest->keys()->first(),
                    $this->assetPath("{$buildDirectory}/{$css}"),
                    $partialManifest->first(),
                    $manifest,
                ]);

                $tags->push($this->makeTagForChunk(
                    $partialManifest->keys()->first(),
                    $this->assetPath("{$buildDirectory}/{$css}"),
                    $partialManifest->first(),
                    $manifest
                ));
            }
        }

        [$stylesheets, $scripts] = $tags->unique()->partition(fn($tag) => str_starts_with($tag, '<link'));

        $preloads = $preloads->unique()
            ->sortByDesc(fn($args) => $this->isCssPath($args[1]))
            ->map(fn($args) => $this->makePreloadTagForChunk(...$args));

        return new HtmlString($preloads->join('') . $stylesheets->join('') . $scripts->join(''));
    }

    /**
     * Make tag for the given chunk.
     *
     * @param string $src
     * @param string $url
     * @param array|null $chunk
     * @param array|null $manifest
     * @return string
     */
    protected function makeTagForChunk(string $src, string $url, ?array $chunk, ?array $manifest): string
    {
        if (
            $this->nonce === null
            && $this->integrityKey !== false
            && !array_key_exists($this->integrityKey, $chunk ?? [])
            && $this->scriptTagAttributesResolvers === []
            && $this->styleTagAttributesResolvers === []) {
            return $this->makeTag($url);
        }

        if ($this->isCssPath($url)) {
            return $this->makeStylesheetTagWithAttributes(
                $url,
                $this->resolveStylesheetTagAttributes($src, $url, $chunk, $manifest)
            );
        }

        return $this->makeScriptTagWithAttributes(
            $url,
            $this->resolveScriptTagAttributes($src, $url, $chunk, $manifest)
        );
    }

    /**
     * Make a preload tag for the given chunk.
     *
     * @param string $src
     * @param string $url
     * @param array $chunk
     * @param array $manifest
     * @return string
     */
    protected function makePreloadTagForChunk(string $src, string $url, array $chunk, array $manifest): string
    {
        $attributes = $this->resolvePreloadTagAttributes($src, $url, $chunk, $manifest);

        if ($attributes === false) {
            return '';
        }

        $this->preloadedAssets[$url] = $this->parseAttributes(
            Collection::make($attributes)->forget('href')->all()
        );

        return '<link ' . implode(' ', $this->parseAttributes($attributes)) . ' />';
    }

    /**
     * Resolve the attributes for the chunks generated script tag.
     *
     * @param string $src
     * @param string $url
     * @param array|null $chunk
     * @param array|null $manifest
     * @return array
     */
    protected function resolveScriptTagAttributes(string $src, string $url, ?array $chunk, ?array $manifest): array
    {
        $attributes = $this->integrityKey !== false
            ? ['integrity' => $chunk[$this->integrityKey] ?? false]
            : [];

        foreach ($this->scriptTagAttributesResolvers as $resolver) {
            $attributes = array_merge($attributes, $resolver($src, $url, $chunk, $manifest));
        }

        return $attributes;
    }

    /**
     * Resolve the attributes for the chunks generated stylesheet tag.
     *
     * @param string $src
     * @param string $url
     * @param array|null $chunk
     * @param array|null $manifest
     * @return array
     */
    protected function resolveStylesheetTagAttributes(string $src, string $url, ?array $chunk, ?array $manifest): array
    {
        $attributes = $this->integrityKey !== false
            ? ['integrity' => $chunk[$this->integrityKey] ?? false]
            : [];

        foreach ($this->styleTagAttributesResolvers as $resolver) {
            $attributes = array_merge($attributes, $resolver($src, $url, $chunk, $manifest));
        }

        return $attributes;
    }

    /**
     * Resolve the attributes for the chunks generated preload tag.
     *
     * @param string $src
     * @param string $url
     * @param array $chunk
     * @param array $manifest
     * @return array|false
     */
    protected function resolvePreloadTagAttributes(string $src, string $url, array $chunk, array $manifest): bool|array
    {
        $attributes = $this->isCssPath($url) ? [
            'rel' => 'preload',
            'as' => 'style',
            'href' => $url,
            'nonce' => $this->nonce ?? false,
            'crossorigin' => $this->resolveStylesheetTagAttributes($src, $url, $chunk, $manifest)['crossorigin'] ?? false,
        ] : [
            'rel' => 'modulepreload',
            'href' => $url,
            'nonce' => $this->nonce ?? false,
            'crossorigin' => $this->resolveScriptTagAttributes($src, $url, $chunk, $manifest)['crossorigin'] ?? false,
        ];

        $attributes = $this->integrityKey !== false
            ? array_merge($attributes, ['integrity' => $chunk[$this->integrityKey] ?? false])
            : $attributes;

        foreach ($this->preloadTagAttributesResolvers as $resolver) {
            if (false === ($resolvedAttributes = $resolver($src, $url, $chunk, $manifest))) {
                return false;
            }

            $attributes = array_merge($attributes, $resolvedAttributes);
        }

        return $attributes;
    }

    /**
     * Generate an appropriate tag for the given URL in HMR mode.
     *
     * @param string $url
     * @return string
     * @deprecated Will be removed in a future Laravel version.
     *
     */
    protected function makeTag(string $url): string
    {
        if ($this->isCssPath($url)) {
            return $this->makeStylesheetTag($url);
        }

        return $this->makeScriptTag($url);
    }

    /**
     * Generate a script tag for the given URL.
     *
     * @param string $url
     * @return string
     * @deprecated Will be removed in a future Laravel version.
     *
     */
    protected function makeScriptTag(string $url): string
    {
        return $this->makeScriptTagWithAttributes($url, []);
    }

    /**
     * Generate a stylesheet tag for the given URL in HMR mode.
     *
     * @param string $url
     * @return string
     * @deprecated Will be removed in a future Laravel version.
     *
     */
    protected function makeStylesheetTag(string $url): string
    {
        return $this->makeStylesheetTagWithAttributes($url, []);
    }

    /**
     * Generate a script tag with attributes for the given URL.
     *
     * @param string $url
     * @param array $attributes
     * @return string
     */
    protected function makeScriptTagWithAttributes(string $url, array $attributes): string
    {
        $attributes = $this->parseAttributes(array_merge([
            'type' => 'module',
            'src' => $url,
            'nonce' => $this->nonce ?? false,
        ], $attributes));

        return '<script ' . implode(' ', $attributes) . '></script>';
    }

    /**
     * Generate a link tag with attributes for the given URL.
     *
     * @param string $url
     * @param array $attributes
     * @return string
     */
    protected function makeStylesheetTagWithAttributes(string $url, array $attributes): string
    {
        $attributes = $this->parseAttributes(array_merge([
            'rel' => 'stylesheet',
            'href' => $url,
            'nonce' => $this->nonce ?? false,
        ], $attributes));

        return '<link ' . implode(' ', $attributes) . ' />';
    }

    /**
     * Determine whether the given path is a CSS file.
     *
     * @param string $path
     * @return bool
     */
    protected function isCssPath(string $path): bool
    {
        return preg_match('/\.(css|less|sass|scss|styl|stylus|pcss|postcss)$/', $path) === 1;
    }

    /**
     * Parse the attributes into key="value" strings.
     *
     * @param array $attributes
     * @return array
     */
    protected function parseAttributes(array $attributes): array
    {
        return Collection::make($attributes)
            ->reject(fn($value, $key) => in_array($value, [false, null], true))
            ->flatMap(fn($value, $key) => $value === true ? [$key] : [$key => $value])
            ->map(fn($value, $key) => is_int($key) ? $value : $key . '="' . $value . '"')
            ->values()
            ->all();
    }

    /**
     * Generate React refresh runtime script.
     *
     * @return \Mini\Support\HtmlString|void
     */
    public function reactRefresh()
    {
        if (!$this->isRunningHot()) {
            return;
        }

        $attributes = $this->parseAttributes([
            'nonce' => $this->cspNonce(),
        ]);

        return new HtmlString(
            sprintf(
                <<<'HTML'
                <script type="module" %s>
                    import RefreshRuntime from '%s'
                    RefreshRuntime.injectIntoGlobalHook(window)
                    window.$RefreshReg$ = () => {}
                    window.$RefreshSig$ = () => (type) => type
                    window.__vite_plugin_react_preamble_installed__ = true
                </script>
                HTML,
                implode(' ', $attributes),
                $this->hotAsset('@react-refresh')
            )
        );
    }

    /**
     * Get the path to a given asset when running in HMR mode.
     *
     * @param $asset
     * @return string
     */
    protected function hotAsset($asset): string
    {
        return rtrim(file_get_contents($this->hotFile())) . '/' . $asset;
    }

    /**
     * Get the URL for an asset.
     *
     * @param string $asset
     * @param string|null $buildDirectory
     * @return string
     * @throws Exception
     */
    public function asset(string $asset, string $buildDirectory = null): string
    {
        $buildDirectory ??= $this->buildDirectory;

        if ($this->isRunningHot()) {
            return $this->hotAsset($asset);
        }

        $chunk = $this->chunk($this->manifest($buildDirectory), $asset);

        return $this->assetPath($buildDirectory . '/' . $chunk['file']);
    }

    /**
     * Get the content of a given asset.
     *
     * @param string $asset
     * @param string|null $buildDirectory
     * @return string
     *
     * @throws Exception
     */
    public function content(string $asset, string $buildDirectory = null): string
    {
        $buildDirectory ??= $this->buildDirectory;

        $chunk = $this->chunk($this->manifest($buildDirectory), $asset);

        $path = public_path($buildDirectory . '/' . $chunk['file']);

        if (!is_file($path) || !file_exists($path)) {
            throw new Exception("Unable to locate file from Vite manifest: {$path}.");
        }

        return file_get_contents($path);
    }

    /**
     * Generate an asset path for the application.
     *
     * @param string $path
     * @param bool|null $secure
     * @return string
     */
    protected function assetPath(string $path, bool $secure = null): string
    {
        return asset($path, $secure);
    }

    /**
     * Get the the manifest file for the given build directory.
     *
     * @param string $buildDirectory
     * @return array
     * @throws ViteManifestNotFoundException
     */
    protected function manifest(string $buildDirectory): array
    {
        $path = $this->manifestPath($buildDirectory);

        if (!isset(static::$manifests[$path])) {
            if (!is_file($path)) {
                throw new ViteManifestNotFoundException("Vite manifest not found at: $path");
            }

            static::$manifests[$path] = json_decode(file_get_contents($path), true);
        }

        return static::$manifests[$path];
    }

    /**
     * Get the path to the manifest file for the given build directory.
     *
     * @param string $buildDirectory
     * @return string
     */
    protected function manifestPath(string $buildDirectory): string
    {
        return public_path($buildDirectory . '/' . $this->manifestFilename);
    }

    /**
     * Get a unique hash representing the current manifest, or null if there is no manifest.
     *
     * @param string|null $buildDirectory
     * @return string|null
     */
    public function manifestHash(string $buildDirectory = null): ?string
    {
        $buildDirectory ??= $this->buildDirectory;

        if ($this->isRunningHot()) {
            return null;
        }

        if (!is_file($path = $this->manifestPath($buildDirectory))) {
            return null;
        }

        return md5_file($path) ?: null;
    }

    /**
     * Get the chunk for the given entry point / asset.
     *
     * @param array $manifest
     * @param string $file
     * @return array
     *
     * @throws Exception
     */
    protected function chunk(array $manifest, string $file): array
    {
        if (!isset($manifest[$file])) {
            throw new RuntimeException("Unable to locate file in Vite manifest: {$file}.");
        }

        return $manifest[$file];
    }

    /**
     * Determine if the HMR server is running.
     *
     * @return bool
     */
    public function isRunningHot(): bool
    {
        return is_file($this->hotFile());
    }

    /**
     * Get the Vite tag content as a string of HTML.
     *
     * @return string
     * @throws Exception
     */
    public function toHtml(): string
    {
        return $this->__invoke($this->entryPoints)->toHtml();
    }
}
