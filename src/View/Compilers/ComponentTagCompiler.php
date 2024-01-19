<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\View\Compilers;

use Mini\Container\Container;
use Mini\Contracts\Foundation\Application;
use Mini\Contracts\View\Factory;
use Mini\Filesystem\Filesystem;
use Mini\Support\Collection;
use Mini\Support\Str;
use Mini\View\AnonymousComponent;
use InvalidArgumentException;
use ReflectionClass;

/**
 * @author Spatie bvba <info@spatie.be>
 * @author Taylor Otwell <taylor@laravel.com>
 */
class ComponentTagCompiler
{
    /**
     * The Blade compiler instance.
     *
     * @var BladeCompiler
     */
    protected BladeCompiler $blade;

    /**
     * The component class aliases.
     *
     * @var array
     */
    protected array $aliases = [];

    /**
     * The "bind:" attributes that have been compiled for the current component.
     *
     * @var array
     */
    protected array $boundAttributes = [];

    /**
     * Create new component tag compiler.
     *
     * @param array $aliases
     * @param BladeCompiler|null
     * @return void
     */
    public function __construct(array $aliases = [], ?BladeCompiler $blade = null)
    {
        $this->aliases = $aliases;

        $this->blade = $blade ?: new BladeCompiler(new Filesystem, sys_get_temp_dir());
    }

    /**
     * Compile the component and slot tags within the given string.
     *
     * @param string $value
     * @return string
     */
    public function compile(string $value): string
    {
        $value = $this->compileSlots($value);

        return $this->compileTags($value);
    }

    /**
     * Compile the tags within the given string.
     *
     * @param string $value
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    public function compileTags(string $value): string
    {
        $value = $this->compileSelfClosingTags($value);
        $value = $this->compileOpeningTags($value);
        $value = $this->compileClosingTags($value);

        return $value;
    }

    /**
     * Compile the opening tags within the given string.
     *
     * @param string $value
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    protected function compileOpeningTags(string $value): string
    {
        $pattern = "/
            <
                \s*
                x[-\:]([\w\-\:\.]*)
                (?<attributes>
                    (?:
                        \s+
                        (?:
                            (?:
                                \{\{\s*\\\$attributes(?:[^}]+?)?\s*\}\}
                            )
                            |
                            (?:
                                [\w\-:.@]+
                                (
                                    =
                                    (?:
                                        \\\"[^\\\"]*\\\"
                                        |
                                        \'[^\']*\'
                                        |
                                        [^\'\\\"=<>]+
                                    )
                                )?
                            )
                        )
                    )*
                    \s*
                )
                (?<![\/=\-])
            >
        /x";

        return preg_replace_callback($pattern, function (array $matches) {
            $this->boundAttributes = [];

            $attributes = $this->getAttributesFromAttributeString($matches['attributes']);

            return $this->componentString($matches[1], $attributes);
        }, $value);
    }

    /**
     * Compile the self-closing tags within the given string.
     *
     * @param string $value
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    protected function compileSelfClosingTags(string $value): string
    {
        $pattern = "/
            <
                \s*
                x[-\:]([\w\-\:\.]*)
                \s*
                (?<attributes>
                    (?:
                        \s+
                        (?:
                            (?:
                                \{\{\s*\\\$attributes(?:[^}]+?)?\s*\}\}
                            )
                            |
                            (?:
                                [\w\-:.@]+
                                (
                                    =
                                    (?:
                                        \\\"[^\\\"]*\\\"
                                        |
                                        \'[^\']*\'
                                        |
                                        [^\'\\\"=<>]+
                                    )
                                )?
                            )
                        )
                    )*
                    \s*
                )
            \/>
        /x";

        return preg_replace_callback($pattern, function (array $matches) {
            $this->boundAttributes = [];

            $attributes = $this->getAttributesFromAttributeString($matches['attributes']);

            return $this->componentString($matches[1], $attributes) . "\n@endcomponentClass ";
        }, $value);
    }

    /**
     * Compile the Blade component string for the given component and attributes.
     *
     * @param string $component
     * @param array $attributes
     * @return string
     */
    protected function componentString(string $component, array $attributes): string
    {
        $class = $this->componentClass($component);

        [$data, $attributes] = $this->partitionDataAndAttributes($class, $attributes);

        $data = $data->mapWithKeys(static function ($value, $key) {
            return [Str::camel($key) => $value];
        });

        // If the component doesn't exists as a class we'll assume it's a class-less
        // component and pass the component as a view parameter to the data so it
        // can be accessed within the component and we can render out the view.
        if (!class_exists($class)) {
            $parameters = [
                'view' => "'$class'",
                'data' => '[' . $this->attributesToString($data->all(), $escapeBound = false) . ']',
            ];

            $class = AnonymousComponent::class;
        } else {
            $parameters = $data->all();
        }

        return " @component('{$class}', '{$component}', [" . $this->attributesToString($parameters, $escapeBound = false) . '])
<?php $component->withAttributes([' . $this->attributesToString($attributes->all()) . ']); ?>';
    }

    /**
     * Get the component class for a given component alias.
     *
     * @param string $component
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    public function componentClass(string $component): string
    {
        $viewFactory = Container::getInstance()->make(Factory::class);

        if (isset($this->aliases[$component])) {
            if (class_exists($alias = $this->aliases[$component])) {
                return $alias;
            }

            if ($viewFactory->exists($alias)) {
                return $alias;
            }

            throw new InvalidArgumentException(
                "Unable to locate class or view [{$alias}] for component [{$component}]."
            );
        }

        if (class_exists($class = $this->guessClassName($component))) {
            return $class;
        }

        if ($viewFactory->exists($view = "components.{$component}")) {
            return $view;
        }

        throw new InvalidArgumentException(
            "Unable to locate a class or view for component [{$component}]."
        );
    }

    /**
     * Guess the class name for the given component.
     *
     * @param string $component
     * @return string
     */
    public function guessClassName(string $component): string
    {
        $namespace = Container::getInstance()
            ->make(Application::class)
            ->getNamespace();

        $componentPieces = array_map(static function ($componentPiece) {
            return ucfirst(Str::camel($componentPiece));
        }, explode('.', $component));

        return $namespace . 'View\\Components\\' . implode('\\', $componentPieces);
    }

    /**
     * Partition the data and extra attributes from the given array of attributes.
     *
     * @param string $class
     * @param array $attributes
     * @return Collection|array
     * @throws \ReflectionException
     */
    public function partitionDataAndAttributes(string $class, array $attributes): Collection|array
    {
        // If the class doesn't exists, we'll assume it's a class-less component and
        // return all of the attributes as both data and attributes since we have
        // now way to partition them. The user can exclude attributes manually.
        if (!class_exists($class)) {
            return [collect($attributes), collect($attributes)];
        }

        $constructor = (new ReflectionClass($class))->getConstructor();

        $parameterNames = $constructor
            ? collect($constructor->getParameters())->map->getName()->all()
            : [];

        return collect($attributes)->partition(static function ($value, $key) use ($parameterNames) {
            return in_array(Str::camel($key), $parameterNames, true);
        });
    }

    /**
     * Compile the closing tags within the given string.
     *
     * @param string $value
     * @return string
     */
    protected function compileClosingTags(string $value): string
    {
        return preg_replace("/<\/\s*x[-\:][\w\-\:\.]*\s*>/", ' @endcomponentClass ', $value);
    }

    /**
     * Compile the slot tags within the given string.
     *
     * @param string $value
     * @return string
     */
    public function compileSlots(string $value): string
    {
        $value = preg_replace_callback('/<\s*x[\-\:]slot\s+name=(?<name>(\"[^\"]+\"|\\\'[^\\\']+\\\'|[^\s>]+))\s*>/', function ($matches) {
            return " @slot('" . $this->stripQuotes($matches['name']) . "') ";
        }, $value);

        return preg_replace('/<\/\s*x[\-\:]slot[^>]*>/', ' @endslot', $value);
    }

    /**
     * Get an array of attributes from the given attribute string.
     *
     * @param string $attributeString
     * @return array
     */
    protected function getAttributesFromAttributeString(string $attributeString): array
    {
        $attributeString = $this->parseAttributeBag($attributeString);

        $attributeString = $this->parseBindAttributes($attributeString);

        $pattern = '/
            (?<attribute>[\w\-:.@]+)
            (
                =
                (?<value>
                    (
                        \"[^\"]+\"
                        |
                        \\\'[^\\\']+\\\'
                        |
                        [^\s>]+
                    )
                )
            )?
        /x';

        if (!preg_match_all($pattern, $attributeString, $matches, PREG_SET_ORDER)) {
            return [];
        }

        return collect($matches)->mapWithKeys(function ($match) {
            $attribute = $match['attribute'];
            $value = $match['value'] ?? null;

            if (is_null($value)) {
                $value = 'true';

                $attribute = Str::start($attribute, 'bind:');
            }

            $value = $this->stripQuotes($value);

            if (Str::startsWith($attribute, 'bind:')) {
                $attribute = Str::after($attribute, 'bind:');

                $this->boundAttributes[$attribute] = true;
            } else {
                $value = "'" . $this->compileAttributeEchos($value) . "'";
            }

            return [$attribute => $value];
        })->toArray();
    }

    /**
     * Parse the attribute bag in a given attribute string into it's fully-qualified syntax.
     *
     * @param string $attributeString
     * @return string
     */
    protected function parseAttributeBag(string $attributeString): string
    {
        $pattern = "/
            (?:^|\s+)                                        # start of the string or whitespace between attributes
            \{\{\s*(\\\$attributes(?:[^}]+?(?<!\s))?)\s*\}\} # exact match of attributes variable being echoed
        /x";

        return preg_replace($pattern, ' :attributes="$1"', $attributeString);
    }

    /**
     * Parse the "bind" attributes in a given attribute string into their fully-qualified syntax.
     *
     * @param string $attributeString
     * @return string
     */
    protected function parseBindAttributes(string $attributeString): string
    {
        $pattern = "/
            (?:^|\s+)     # start of the string or whitespace between attributes
            :             # attribute needs to start with a semicolon
            ([\w\-:.@]+)  # match the actual attribute name
            =             # only match attributes that have a value
        /xm";

        return preg_replace($pattern, ' bind:$1=', $attributeString);
    }

    /**
     * Compile any Blade echo statements that are present in the attribute string.
     *
     * These echo statements need to be converted to string concatenation statements.
     *
     * @param string $attributeString
     * @return string
     */
    protected function compileAttributeEchos(string $attributeString): string
    {
        $value = $this->blade->compileEchos($attributeString);

        $value = $this->escapeSingleQuotesOutsideOfPhpBlocks($value);

        $value = str_replace('<?php echo ', '\'.', $value);
        return str_replace('; ?>', '.\'', $value);
    }

    /**
     * Escape the single quotes in the given string that are outside of PHP blocks.
     *
     * @param string $value
     * @return string
     */
    protected function escapeSingleQuotesOutsideOfPhpBlocks(string $value): string
    {
        return collect(token_get_all($value))->map(static function ($token) {
            if (!is_array($token)) {
                return $token;
            }

            return $token[0] === T_INLINE_HTML
                ? str_replace("'", "\\'", $token[1])
                : $token[1];
        })->implode('');
    }

    /**
     * Convert an array of attributes to a string.
     *
     * @param array $attributes
     * @param bool $escapeBound
     * @return string
     */
    protected function attributesToString(array $attributes, bool $escapeBound = true): string
    {
        return collect($attributes)
            ->map(function (string $value, string $attribute) use ($escapeBound) {
                return $escapeBound && isset($this->boundAttributes[$attribute]) && $value !== 'true' && !is_numeric($value)
                    ? "'{$attribute}' => \Mini\View\Compilers\BladeCompiler::sanitizeComponentAttribute({$value})"
                    : "'{$attribute}' => {$value}";
            })
            ->implode(',');
    }

    /**
     * Strip any quotes from the given string.
     *
     * @param string $value
     * @return string
     */
    public function stripQuotes(string $value): string
    {
        return Str::startsWith($value, ['"', '\''])
            ? substr($value, 1, -1)
            : $value;
    }
}
