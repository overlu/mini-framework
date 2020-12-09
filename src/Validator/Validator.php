<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Validator;

/**
 * Class Validator
 * @package Mini\Validator
 * @method static void setValidator(string $key, Rule $rule)
 * @method static Rule getValidator(string $key)
 * @method static Validation validate(array $inputs, array $rules, array $messages = [])
 * @method static Validation make(array $inputs, array $rules, array $messages = [])
 * @method static void addValidator(string $ruleName, Rule $rule)
 * @method static void allowRuleOverride(bool $status = false)
 * @method static void setUseHumanizedKeys(bool $useHumanizedKeys = true)
 * @method static Factory setTranslation(string $key, string $translation)
 * @method static Factory setTranslations(array $translations)
 * @method static string getTranslation(string $key)
 * @method static array getTranslations()
 * @method static Factory setMessage(string $key, string $message)
 * @method static Factory setMessages(array $messages)
 * @method static string getMessage(string $key)
 * @method static array getMessages()
 * @method static Factory setAlias(string $key, string $alias)
 * @method static Factory setAliases(array $aliases)
 * @method static string getAlias(string $key)
 * @method static array getAliases()
 * @method static bool isUsingHumanizedKey()
 * @see Factory
 */
class Validator
{
    public static function __callStatic($name, $arguments)
    {
        return app('validator')->{$name}(...$arguments);
    }
}