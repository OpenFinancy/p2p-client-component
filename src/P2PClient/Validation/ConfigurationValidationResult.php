<?php

declare(strict_types=1);

namespace OpenFinancy\Component\P2PClient\Validation;

final class ConfigurationValidationResult
{
    /**
     * @var string[]
     */
    private array $errors = [];

    /**
     * @var string[]
     */
    private array $warnings = [];

    /**
     * @return string[]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    public function addError(string $message): void
    {
        $this->errors[] = $message;
    }

    public function hasErrors(): bool
    {
        return $this->errors !== [];
    }

    /**
     * @return string[]
     */
    public function getWarnings(): array
    {
        return $this->warnings;
    }

    public function addWarning(string $message): void
    {
        $this->warnings[] = $message;
    }

    public function hasWarnings(): bool
    {
        return $this->warnings !== [];
    }

    public function merge(self $other): void
    {
        foreach ($other->errors as $error) {
            $this->errors[] = $error;
        }

        foreach ($other->warnings as $warning) {
            $this->warnings[] = $warning;
        }
    }
}
