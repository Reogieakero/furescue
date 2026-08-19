<?php

namespace App\Validation;


class Validator
{
    private array $data;
    
    private array $errors = [];
    private ?string $lastField = null;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function required(string $field): self
    {
        $this->lastField = $field;
        if (!isset($this->data[$field]) || $this->data[$field] === '' || $this->data[$field] === null) {
            $this->add($field, 'is required');
        }
        return $this;
    }

    public function optional(string $field): self
    {
        $this->lastField = $field;
        return $this;
    }

    public function email(string $field = null): self
    {
        $field = $field ?? $this->lastField;
        if ($field === null) return $this;
        if (isset($this->data[$field]) && $this->data[$field] !== '' && !filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
            $this->add($field, 'must be a valid email');
        }
        return $this;
    }

    public function string(string $field, int $max = 65535): self
    {
        $this->lastField = $field;
        if (isset($this->data[$field]) && $this->data[$field] !== '') {
            if (!is_string($this->data[$field])) {
                $this->add($field, 'must be a string');
            } elseif (mb_strlen($this->data[$field]) > $max) {
                $this->add($field, "must be at most {$max} characters");
            }
        }
        return $this;
    }

    public function minLen(string $field, int $min): self
    {
        $this->lastField = $field;
        if (isset($this->data[$field]) && $this->data[$field] !== '' && mb_strlen((string) $this->data[$field]) < $min) {
            $this->add($field, "must be at least {$min} characters");
        }
        return $this;
    }

    public function numeric(string $field): self
    {
        $this->lastField = $field;
        if (isset($this->data[$field]) && $this->data[$field] !== '' && !is_numeric($this->data[$field])) {
            $this->add($field, 'must be numeric');
        }
        return $this;
    }

    public function in(string $field, array $allowed): self
    {
        $this->lastField = $field;
        if (isset($this->data[$field]) && $this->data[$field] !== '' && !in_array($this->data[$field], $allowed, true)) {
            $this->add($field, 'must be one of: ' . implode(', ', $allowed));
        }
        return $this;
    }

    public function boolean(string $field): self
    {
        $this->lastField = $field;
        if (isset($this->data[$field]) && $this->data[$field] !== '' && !is_bool($this->data[$field]) && !in_array($this->data[$field], [0, 1, '0', '1', 'true', 'false'], true)) {
            $this->add($field, 'must be a boolean');
        }
        return $this;
    }

    public function latitude(string $field = null): self
    {
        $field = $field ?? $this->lastField;
        if ($field === null) return $this;
        if (isset($this->data[$field]) && $this->data[$field] !== '') {
            $v = (float) $this->data[$field];
            if ($v < -90 || $v > 90) {
                $this->add($field, 'must be between -90 and 90');
            }
        }
        return $this;
    }

    public function longitude(string $field = null): self
    {
        $field = $field ?? $this->lastField;
        if ($field === null) return $this;
        if (isset($this->data[$field]) && $this->data[$field] !== '') {
            $v = (float) $this->data[$field];
            if ($v < -180 || $v > 180) {
                $this->add($field, 'must be between -180 and 180');
            }
        }
        return $this;
    }

    private function add(string $field, string $msg): void
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = $msg;
        }
    }

    public function passes(): bool
    {
        return count($this->errors) === 0;
    }

    
    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(): string
    {
        foreach ($this->errors as $field => $msg) {
            return "$field $msg";
        }
        return 'Validation failed';
    }
}
