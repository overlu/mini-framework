<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Mail\Mailables;

use Mini\Support\Traits\Conditionable;

class Content
{
    use Conditionable;

    /**
     * The Blade view that should be rendered for the mailable.
     *
     * @var string|null
     */
    public ?string $view = null;

    /**
     * The Blade view that should be rendered for the mailable.
     *
     * Alternative syntax for "view".
     *
     * @var string|null
     */
    public ?string $html = null;

    /**
     * The Blade view that represents the text version of the message.
     *
     * @var string|null
     */
    public ?string $text = null;

    /**
     * The Blade view that represents the Markdown version of the message.
     *
     * @var string|null
     */
    public ?string $markdown = null;

    /**
     * The pre-rendered HTML of the message.
     *
     * @var string|null
     */
    public ?string $htmlString = null;

    /**
     * The message's view data.
     *
     * @var array
     */
    public array $with;

    /**
     * Create a new content definition.
     *
     * @param string|null $view
     * @param string|null $html
     * @param string|null $text
     * @param string|null $markdown
     * @param array $with
     * @param string|null $htmlString
     *
     * @named-arguments-supported
     */
    public function __construct(string $view = null, string $html = null, string $text = null, string $markdown = null, array $with = [], string $htmlString = null)
    {
        $this->view = $view;
        $this->html = $html;
        $this->text = $text;
        $this->markdown = $markdown;
        $this->with = $with;
        $this->htmlString = $htmlString;
    }

    /**
     * Set the view for the message.
     *
     * @param string $view
     * @return $this
     */
    public function view(string $view): self
    {
        $this->view = $view;

        return $this;
    }

    /**
     * Set the view for the message.
     *
     * @param string $view
     * @return $this
     */
    public function html(string $view): self
    {
        return $this->view($view);
    }

    /**
     * Set the plain text view for the message.
     *
     * @param string $view
     * @return $this
     */
    public function text(string $view): self
    {
        $this->text = $view;

        return $this;
    }

    /**
     * Set the Markdown view for the message.
     *
     * @param string $view
     * @return $this
     */
    public function markdown(string $view): self
    {
        $this->markdown = $view;

        return $this;
    }

    /**
     * Set the pre-rendered HTML for the message.
     *
     * @param string $html
     * @return $this
     */
    public function htmlString(string $html): self
    {
        $this->htmlString = $html;

        return $this;
    }

    /**
     * Add a piece of view data to the message.
     *
     * @param array|string $key
     * @param mixed|null $value
     * @return $this
     */
    public function with(array|string $key, mixed $value = null): self
    {
        if (is_array($key)) {
            $this->with = array_merge($this->with, $key);
        } else {
            $this->with[$key] = $value;
        }

        return $this;
    }
}
