<?php

namespace App\Exceptions;

use Exception;

/**
 * Base exception class for setup-related errors.
 * 
 * This exception provides structured error handling for the setup wizard
 * with support for user-friendly messages and troubleshooting guidance.
 */
class SetupException extends Exception
{
    protected array $troubleshootingSteps = [];
    protected array $context = [];
    protected string $userMessage = '';

    public function __construct(
        string $message = '',
        int $code = 0,
        ?Exception $previous = null,
        string $userMessage = '',
        array $troubleshootingSteps = [],
        array $context = []
    ) {
        parent::__construct($message, $code, $previous);
        
        $this->userMessage = $userMessage ?: $message;
        $this->troubleshootingSteps = $troubleshootingSteps;
        $this->context = $context;
    }

    /**
     * Get user-friendly error message.
     */
    public function getUserMessage(): string
    {
        return $this->userMessage;
    }

    /**
     * Get troubleshooting steps for the user.
     */
    public function getTroubleshootingSteps(): array
    {
        return $this->troubleshootingSteps;
    }

    /**
     * Get additional context information.
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Check if this exception has troubleshooting steps.
     */
    public function hasTroubleshootingSteps(): bool
    {
        return !empty($this->troubleshootingSteps);
    }

    /**
     * Get formatted error information for display.
     */
    public function getErrorInfo(): array
    {
        return [
            'message' => $this->getUserMessage(),
            'technical_message' => $this->getMessage(),
            'troubleshooting_steps' => $this->getTroubleshootingSteps(),
            'context' => $this->getContext(),
            'has_troubleshooting' => $this->hasTroubleshootingSteps(),
        ];
    }
}