<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Validator\Rules;

use Mini\Support\Traits\Rule\FileTrait;
use Mini\Validator\Helper;
use Mini\Validator\MimeTypeGuesser;
use Mini\Validator\Rule;

class Mimes extends Rule
{
    use FileTrait;

    /** @var string */
    protected string $message = "The :attribute file type must be :allowed_types";

    /** @var string|int */
    protected ?int $maxSize = null;

    /** @var string|int */
    protected ?int $minSize = null;

    /** @var array */
    protected array $allowedTypes = [];

    /**
     * Given $params and assign $this->params
     * @param array $params
     * @return self
     */
    public function fillParameters(array $params): Rule
    {
        $this->allowTypes($params);
        return $this;
    }

    /**
     * Given $types and assign $this->params
     * @param mixed $types
     * @return self
     */
    public function allowTypes($types): Rule
    {
        if (is_string($types)) {
            $types = explode('|', $types);
        }

        $this->params['allowed_types'] = $types;

        return $this;
    }

    /**
     * Check the $value is valid
     * @param mixed $value
     * @return bool
     */
    public function check($value): bool
    {
        $allowedTypes = $this->parameter('allowed_types');

        if ($allowedTypes) {
            $or = $this->validation ? $this->validation->getTranslation('or') : 'or';
            $this->setParameterText('allowed_types', Helper::join(Helper::wraps($allowedTypes, "'"), ', ', ", {$or} "));
        }

        // below is Required rule job
        if (!$this->isValueFromUploadedFiles($value) || $value['error'] === UPLOAD_ERR_NO_FILE) {
            return true;
        }

        if (!$this->isUploadedFile($value)) {
            return false;
        }

        // just make sure there is no error
        if ($value['error']) {
            return false;
        }

        if (!empty($allowedTypes)) {
            $guesser = new MimeTypeGuesser;
            $ext = $guesser->getExtension($value['type']);
            unset($guesser);

            if (!in_array($ext, $allowedTypes, true)) {
                return false;
            }
        }

        return true;
    }
}
