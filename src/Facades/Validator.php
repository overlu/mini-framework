<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Facades;

/**
 * Class Validator
 * @method static void setValidator(string $key, \Mini\Validator\Rule $rule)
 * @method static \Mini\Validator\Rule getValidator(string $key)
 * @method static \Mini\Validator\Validation validate(array $inputs, array $rules, array $messages = [])
 * @method static \Mini\Validator\Validation make(array $inputs, array $rules, array $messages = [])
 * @method static void addValidator(string $ruleName, \Mini\Validator\Rule $rule)
 * @method static void allowRuleOverride(bool $status = false)
 * @method static void setUseHumanizedKeys(bool $useHumanizedKeys = true)
 * @method static \Mini\Validator\Factory setTranslation(string $key, string $translation)
 * @method static \Mini\Validator\Factory setTranslations(array $translations)
 * @method static string getTranslation(string $key)
 * @method static array getTranslations()
 * @method static \Mini\Validator\Factory setMessage(string $key, string $message)
 * @method static \Mini\Validator\Factory setMessages(array $messages)
 * @method static string getMessage(string $key)
 * @method static array getMessages()
 * @method static \Mini\Validator\Factory setAlias(string $key, string $alias)
 * @method static \Mini\Validator\Factory setAliases(array $aliases)
 * @method static string getAlias(string $key)
 * @method static array getAliases()
 * @method static bool isUsingHumanizedKey()
 * @package Mini\Facades
 *
 * @see \Mini\Validator\Factory
 */
class Validator extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'validator';
    }
}