<?php

namespace App\Services;

/**
 * Represents the result of a cloud storage configuration validation
 * 
 * This class provides a structured way to return validation results
 * with detailed feedback, errors, warnings, and recommended actions.
 */
class ValidationResult
{
    public function __construct(
        public bool $isValid,
        public array $errors = [],
        public array $warnings = [],
        public ?string $recommendedAction = null,
        public array $metadata = []
    ) {}

    /**
     * Create a successful validation result
     */
    public static function success(array $metadata = []): self
    {
        return new self(
            isValid: true,
            metadata: $metadata
        );
    }

    /**
     * Create a failed validation result
     */
    public static function failure(array $errors, array $warnings = [], ?string $recommendedAction = null, array $metadata = []): self
    {
        return new self(
            isValid: false,
            errors: $errors,
            warnings: $warnings,
            recommendedAction: $recommendedAction,
            metadata: $metadata
        );
    }

    /**
     * Create a validation result with warnings but still valid
     */
    public static function withWarnings(array $warnings, array $metadata = []): self
    {
        return new self(
            isValid: true,
            warnings: $warnings,
            metadata: $metadata
        );
    }

    /**
     * Add an error to the validation result
     */
    public function addError(string $error): self
    {
        $this->errors[] = $error;
        $this->isValid = false;
        return $this;
    }

    /**
     * Add a warning to the validation result
     */
    public function addWarning(string $warning): self
    {
        $this->warnings[] = $warning;
        return $this;
    }

    /**
     * Set the recommended action
     */
    public function setRecommendedAction(string $action): self
    {
        $this->recommendedAction = $action;
        return $this;
    }

    /**
     * Add metadata
     */
    public function addMetadata(string $key, mixed $value): self
    {
        $this->metadata[$key] = $value;
        return $this;
    }

    /**
     * Check if there are any errors
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Check if there are any warnings
     */
    public function hasWarnings(): bool
    {
        return !empty($this->warnings);
    }

    /**
     * Get the first error message
     */
    public function getFirstError(): ?string
    {
        return $this->errors[0] ?? null;
    }

    /**
     * Get all error messages as a single string
     */
    public function getErrorsAsString(string $separator = '; '): string
    {
        return implode($separator, $this->errors);
    }

    /**
     * Get all warning messages as a single string
     */
    public function getWarningsAsString(string $separator = '; '): string
    {
        return implode($separator, $this->warnings);
    }

    /**
     * Convert to array for JSON serialization
     */
    public function toArray(): array
    {
        return [
            'is_valid' => $this->isValid,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'recommended_action' => $this->recommendedAction,
            'metadata' => $this->metadata,
        ];
    }

    /**
     * Create from array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            isValid: $data['is_valid'] ?? false,
            errors: $data['errors'] ?? [],
            warnings: $data['warnings'] ?? [],
            recommendedAction: $data['recommended_action'] ?? null,
            metadata: $data['metadata'] ?? []
        );
    }
}