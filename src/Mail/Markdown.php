<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Mail;

use Mini\Contracts\View\Factory as ViewFactory;
use Mini\Support\HtmlString;
use Mini\Support\Str;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\MarkdownConverter;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;

class Markdown
{
    /**
     * The view factory implementation.
     *
     * @var ViewFactory
     */
    protected ViewFactory $view;

    /**
     * The current theme being used when generating emails.
     *
     * @var string
     */
    protected mixed $theme = 'default';

    /**
     * The registered component paths.
     *
     * @var array
     */
    protected array $componentPaths = [];

    /**
     * Create a new Markdown renderer instance.
     *
     * @param ViewFactory $view
     * @param array $options
     * @return void
     */
    public function __construct(ViewFactory $view, array $options = [])
    {
        $this->view = $view;
        $this->theme = $options['theme'] ?? 'default';
        $this->loadComponentsFrom($options['paths'] ?? []);
    }

    /**
     * Render the Markdown template into HTML.
     *
     * @param string $view
     * @param array $data
     * @param CssToInlineStyles|null $inliner
     * @return HtmlString
     */
    public function render(string $view, array $data = [], CssToInlineStyles $inliner = null): HtmlString
    {
        $this->view->flushFinderCache();

        $contents = $this->view->replaceNamespace(
            'mail', $this->htmlComponentPaths()
        )->make($view, $data)->render();

        if ($this->view->exists($customTheme = Str::start($this->theme, 'mail.'))) {
            $theme = $customTheme;
        } else {
            $theme = str_contains($this->theme, '::')
                ? $this->theme
                : 'mail::themes.' . $this->theme;
        }

        return new HtmlString(($inliner ?: new CssToInlineStyles)->convert(
            $contents, $this->view->make($theme, $data)->render()
        ));
    }

    /**
     * Render the Markdown template into text.
     *
     * @param string $view
     * @param array $data
     * @return HtmlString
     */
    public function renderText(string $view, array $data = []): HtmlString
    {
        $this->view->flushFinderCache();

        $contents = $this->view->replaceNamespace(
            'mail', $this->textComponentPaths()
        )->make($view, $data)->render();

        return new HtmlString(
            html_entity_decode(preg_replace("/[\r\n]{2,}/", "\n\n", $contents), ENT_QUOTES, 'UTF-8')
        );
    }

    /**
     * Parse the given Markdown text into HTML.
     *
     * @param string $text
     * @return HtmlString
     */
    public static function parse(string $text): HtmlString
    {
        $environment = new Environment([
            'allow_unsafe_links' => false,
        ]);

        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addExtension(new TableExtension);

        $converter = new MarkdownConverter($environment);

        return new HtmlString($converter->convert($text)->getContent());
    }

    /**
     * Get the HTML component paths.
     *
     * @return array
     */
    public function htmlComponentPaths(): array
    {
        return array_map(function ($path) {
            return $path . '/html';
        }, $this->componentPaths());
    }

    /**
     * Get the text component paths.
     *
     * @return array
     */
    public function textComponentPaths(): array
    {
        return array_map(function ($path) {
            return $path . '/text';
        }, $this->componentPaths());
    }

    /**
     * Get the component paths.
     *
     * @return array
     */
    protected function componentPaths(): array
    {
        return array_unique(array_merge($this->componentPaths, [
            __DIR__ . '/resources/views',
        ]));
    }

    /**
     * Register new mail component paths.
     *
     * @param array $paths
     * @return void
     */
    public function loadComponentsFrom(array $paths = []): void
    {
        $this->componentPaths = $paths;
    }

    /**
     * Set the default theme to be used.
     *
     * @param string $theme
     * @return $this
     */
    public function theme(string $theme): self
    {
        $this->theme = $theme;

        return $this;
    }

    /**
     * Get the theme currently being used by the renderer.
     *
     * @return string
     */
    public function getTheme(): string
    {
        return $this->theme;
    }
}
