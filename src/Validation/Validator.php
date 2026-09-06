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
        if (!array_key_exists($field, $this->data) || $this->isBlank($this->data[$field])) {
            $this->add($field, 'is required');
        }
        return $this;
    }

    public function optional(string $field): self
    {
        $this->lastField = $field;
        return $this;
    }

    public function email(?string $field = null): self
    {
        $field = $field ?? $this->lastField;
        if ($field === null) {
            return $this;
        }
        if ($this->hasValue($field) && !filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
            $this->add($field, 'must be a valid email');
        }
        return $this;
    }

    public function string(string|int|null $field = null, int $max = 65535): self
    {
        if (is_int($field)) {
            $max = $field;
            $field = $this->lastField;
        }
        $field = $field ?? $this->lastField;
        if ($field === null) {
            return $this;
        }
        $this->lastField = $field;
        if (!$this->hasValue($field)) {
            return $this;
        }
        if (!is_string($this->data[$field])) {
            $this->add($field, 'must be a string');
        } elseif (mb_strlen($this->data[$field]) > $max) {
            $this->add($field, "must be at most {$max} characters");
        }
        return $this;
    }

    public function minLen(string|int|null $field = null, ?int $min = null): self
    {
        if (is_int($field) && $min === null) {
            $min = $field;
            $field = $this->lastField;
        }
        $field = $field ?? $this->lastField;
        if ($field === null || $min === null) {
            return $this;
        }
        $this->lastField = $field;
        if ($this->hasValue($field) && mb_strlen((string) $this->data[$field]) < $min) {
            $this->add($field, "must be at least {$min} characters");
        }
        return $this;
    }

    public function numeric(?string $field = null): self
    {
        $field = $field ?? $this->lastField;
        if ($field === null) {
            return $this;
        }
        $this->lastField = $field;
        if ($this->hasValue($field) && !is_numeric($this->data[$field])) {
            $this->add($field, 'must be numeric');
        }
        return $this;
    }

    public function min(?string $field = null, ?float $min = null): self
    {
        if ($field !== null && $min === null && is_numeric($field) && $this->lastField !== null) {
            $min = (float) $field;
            $field = $this->lastField;
        }
        $field = $field ?? $this->lastField;
        if ($field === null || $min === null) {
            return $this;
        }
        $this->lastField = $field;
        if ($this->hasValue($field) && is_numeric($this->data[$field]) && (float) $this->data[$field] < $min) {
            $this->add($field, "must be at least {$min}");
        }
        return $this;
    }

    public function in(string $field, array $allowed): self
    {
        $this->lastField = $field;
        if ($this->hasValue($field) && !in_array($this->data[$field], $allowed, true)) {
            $this->add($field, 'must be one of: ' . implode(', ', $allowed));
        }
        return $this;
    }

    public function boolean(?string $field = null): self
    {
        $field = $field ?? $this->lastField;
        if ($field === null) {
            return $this;
        }
        $this->lastField = $field;
        if ($this->hasValue($field) && !is_bool($this->data[$field]) && !in_array($this->data[$field], [0, 1, '0', '1', 'true', 'false'], true)) {
            $this->add($field, 'must be a boolean');
        }
        return $this;
    }

    public function uuid(?string $field = null): self
    {
        $field = $field ?? $this->lastField;
        if ($field === null) {
            return $this;
        }
        $this->lastField = $field;
        if (!$this->hasValue($field)) {
            return $this;
        }
        $value = $this->data[$field];
        if (!is_string($value) || !preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value)) {
            $this->add($field, 'must be a valid UUID');
        }
        return $this;
    }

    public function stringOrStringList(?string $field = null, int $max = 65535): self
    {
        $field = $field ?? $this->lastField;
        if ($field === null) {
            return $this;
        }
        $this->lastField = $field;
        if (!$this->hasValue($field)) {
            return $this;
        }
        $value = $this->data[$field];
        if (is_string($value)) {
            if (mb_strlen($value) > $max) {
                $this->add($field, "must be at most {$max} characters");
            }
            return $this;
        }
        if (!is_array($value) || !array_is_list($value)) {
            $this->add($field, 'must be a string or an array of strings');
            return $this;
        }
        foreach ($value as $item) {
            if (!is_string($item)) {
                $this->add($field, 'must be a string or an array of strings');
                return $this;
            }
        }
        return $this;
    }

    public function latitude(?string $field = null): self
    {
        $field = $field ?? $this->lastField;
        if ($field === null) {
            return $this;
        }
        if ($this->hasValue($field)) {
            if (!is_numeric($this->data[$field])) {
                $this->add($field, 'must be numeric');
                return $this;
            }
            $v = (float) $this->data[$field];
            if ($v < -90 || $v > 90) {
                $this->add($field, 'must be between -90 and 90');
            }
        }
        return $this;
    }

    public function longitude(?string $field = null): self
    {
        $field = $field ?? $this->lastField;
        if ($field === null) {
            return $this;
        }
        if ($this->hasValue($field)) {
            if (!is_numeric($this->data[$field])) {
                $this->add($field, 'must be numeric');
                return $this;
            }
            $v = (float) $this->data[$field];
            if ($v < -180 || $v > 180) {
                $this->add($field, 'must be between -180 and 180');
            }
        }
        return $this;
    }

    private function hasValue(string $field): bool
    {
        return array_key_exists($field, $this->data) && !$this->isBlank($this->data[$field]);
    }

    private function isBlank(mixed $value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        return is_string($value) && trim($value) === '';
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
