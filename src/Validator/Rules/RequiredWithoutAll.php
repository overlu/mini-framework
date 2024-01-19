<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Validator\Rules;

use Mini\Exception\MissingRequiredParameterException;
use Mini\Validator\Rule;

class RequiredWithoutAll extends Required
{
    /** @var bool */
    protected bool $implicit = true;

    /** @var string */
    protected string $message = "The :attribute is required";

    /**
     * Given $params and assign $this->params
     * @param array $params
     * @return self
     */
    public function fillParameters(array $params): Rule
    {
        $this->params['fields'] = $params;
        return $this;
    }

    /**
     * Check the $value is valid
     * @param mixed $value
     * @return bool
     */
    public function check($value): bool
    {
        $this->requireParameters(['fields']);
        $fields = $this->parameter('fields');
        $validator = $this->validation->getValidator();
        $requiredValidator = $validator('required');

        foreach ($fields as $field) {
            if ($this->validation->hasValue($field)) {
                return true;
            }
        }

        $this->setAttributeAsRequired();
        return $requiredValidator->check($value);
    }
}
