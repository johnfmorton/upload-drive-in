<?php

namespace App\Services;

use Illuminate\Support\Collection;

class BulkOperationResult
{
    protected Collection $successful;
    protected Collection $failed;
    protected array $summary;

    public function __construct()
    {
        $this->successful = collect();
        $this->failed = collect();
        $this->summary = [
            'total' => 0,
            'successful' => 0,
            'failed' => 0,
            'operation' => null,
            'started_at' => now(),
            'completed_at' => null,
        ];
    }

    /**
     * Add a successful operation result.
     */
    public function addSuccess(int $id, array $data = []): self
    {
        $this->successful->push([
            'id' => $id,
            'data' => $data,
            'timestamp' => now(),
        ]);

        $this->updateSummary();
        return $this;
    }

    /**
     * Add a failed operation result.
     */
    public function addFailure(int $id, string $error, array $context = []): self
    {
        $this->failed->push([
            'id' => $id,
            'error' => $error,
            'context' => $context,
            'timestamp' => now(),
        ]);

        $this->updateSummary();
        return $this;
    }

    /**
     * Set the operation type and total count.
     */
    public function setOperation(string $operation, int $total): self
    {
        $this->summary['operation'] = $operation;
        $this->summary['total'] = $total;
        return $this;
    }

    /**
     * Mark the operation as completed.
     */
    public function complete(): self
    {
        $this->summary['completed_at'] = now();
        return $this;
    }

    /**
     * Check if the operation was completely successful.
     */
    public function isCompleteSuccess(): bool
    {
        return $this->failed->isEmpty() && $this->successful->isNotEmpty();
    }

    /**
     * Check if the operation was a complete failure.
     */
    public function isCompleteFailure(): bool
    {
        return $this->successful->isEmpty() && $this->failed->isNotEmpty();
    }

    /**
     * Check if the operation had partial success.
     */
    public function isPartialSuccess(): bool
    {
        return $this->successful->isNotEmpty() && $this->failed->isNotEmpty();
    }

    /**
     * Get successful results.
     */
    public function getSuccessful(): Collection
    {
        return $this->successful;
    }

    /**
     * Get failed results.
     */
    public function getFailed(): Collection
    {
        return $this->failed;
    }

    /**
     * Get operation summary.
     */
    public function getSummary(): array
    {
        return $this->summary;
    }

    /**
     * Get user-friendly status message.
     */
    public function getStatusMessage(): string
    {
        $operation = $this->summary['operation'] ?? 'operation';
        $successful = $this->summary['successful'];
        $failed = $this->summary['failed'];
        $total = $this->summary['total'];

        if ($this->isCompleteSuccess()) {
            return "Successfully completed {$operation} for all {$total} items.";
        }

        if ($this->isCompleteFailure()) {
            return "Failed to complete {$operation} for all {$total} items.";
        }

        if ($this->isPartialSuccess()) {
            return "Completed {$operation} for {$successful} of {$total} items. {$failed} items failed.";
        }

        return "No items were processed for {$operation}.";
    }

    /**
     * Convert to array for JSON response.
     */
    public function toArray(): array
    {
        return [
            'success' => $this->isCompleteSuccess(),
            'partial_success' => $this->isPartialSuccess(),
            'message' => $this->getStatusMessage(),
            'summary' => $this->summary,
            'results' => [
                'successful' => $this->successful->toArray(),
                'failed' => $this->failed->toArray(),
            ],
        ];
    }

    /**
     * Update the summary counts.
     */
    protected function updateSummary(): void
    {
        $this->summary['successful'] = $this->successful->count();
        $this->summary['failed'] = $this->failed->count();
    }
}