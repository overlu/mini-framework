<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Validator\Rules;

use Mini\Support\Traits\Rule\FileTrait;
use Mini\Validator\Rule;

class Required extends Rule
{
    use FileTrait;

    /** @var bool */
    protected bool $implicit = true;

    /** @var string */
    protected string $message = "The :attribute is required";

    /**
     * Check the $value is valid
     * @param mixed $value
     * @return bool
     */
    public function check($value): bool
    {
        $this->setAttributeAsRequired();

        if ($this->attribute && $this->attribute->hasRule('uploaded_file')) {
            return $this->isValueFromUploadedFiles($value) && $value['error'] !== UPLOAD_ERR_NO_FILE;
        }

        if (is_string($value)) {
            return trim($value) !== '';
        }
        if (is_array($value)) {
            return count($value) > 0;
        }
        return !is_null($value);
    }

    /**
     * Set attribute is required if $this->attribute is true
     * @return void
     */
    protected function setAttributeAsRequired(): void
    {
        if ($this->attribute) {
            $this->attribute->setRequired(true);
        }
    }
}
