<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Contracts;

/**
 * Class Validator
 * @method void setValidator(string $key, \Mini\Validator\Rule $rule)
 * @method \Mini\Validator\Rule getValidator(string $key)
 * @method \Mini\Validator\Validation validate(array $inputs, array $rules, array $messages = [])
 * @method \Mini\Validator\Validation make(array $inputs, array $rules, array $messages = [])
 * @method void addValidator(string $ruleName, \Mini\Validator\Rule $rule)
 * @method void allowRuleOverride(bool $status = false)
 * @method void setUseHumanizedKeys(bool $useHumanizedKeys = true)
 * @method \Mini\Validator\Factory setTranslation(string $key, string $translation)
 * @method \Mini\Validator\Factory setTranslations(array $translations)
 * @method string getTranslation(string $key)
 * @method array getTranslations()
 * @method \Mini\Validator\Factory setMessage(string $key, string $message)
 * @method \Mini\Validator\Factory setMessages(array $messages)
 * @method string getMessage(string $key)
 * @method array getMessages()
 * @method \Mini\Validator\Factory setAlias(string $key, string $alias)
 * @method \Mini\Validator\Factory setAliases(array $aliases)
 * @method string getAlias(string $key)
 * @method array getAliases()
 * @method bool isUsingHumanizedKey()
 * @package Mini\Facades
 *
 * @see \Mini\Validator\Factory
 */
interface Validator
{

}
