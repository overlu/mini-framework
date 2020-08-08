<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Validator\Rules;

use Mini\Exceptions\MissingRequiredParameterException;
use Mini\Validator\Rule;

class RequiredUnless extends Required
{
    /** @var bool */
    protected bool $implicit = true;

    /** @var string */
    protected string $message = "The :attribute is required";

    /**
     * Given $params and assign the $this->params
     * @param array $params
     * @return self
     */
    public function fillParameters(array $params): Rule
    {
        $this->params['field'] = array_shift($params);
        $this->params['values'] = $params;
        return $this;
    }

    /**
     * Check the $value is valid
     * @param mixed $value
     * @return bool
     * @throws MissingRequiredParameterException|\Mini\Exceptions\RuleNotFoundException
     */
    public function check($value): bool
    {
        $this->requireParameters(['field', 'values']);

        $anotherAttribute = $this->parameter('field');
        $definedValues = $this->parameter('values');
        $anotherValue = $this->getAttribute()->getValue($anotherAttribute);

        $validator = $this->validation->getValidator();
        $requiredValidator = $validator('required');

        if (!in_array($anotherValue, $definedValues, true)) {
            $this->setAttributeAsRequired();
            return $requiredValidator->check($value);
        }

        return true;
    }
}
