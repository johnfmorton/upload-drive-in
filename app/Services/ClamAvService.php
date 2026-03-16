<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class ClamAvService
{
    /**
     * Scan a file for viruses using the ClamAV daemon.
     *
     * @return array{clean: bool, virus: string|null, error: string|null}
     */
    public function scan(string $filePath): array
    {
        if (! config('filesecurity.clamav.enabled')) {
            return ['clean' => true, 'virus' => null, 'error' => null];
        }

        if (! file_exists($filePath)) {
            return ['clean' => false, 'virus' => null, 'error' => 'File not found'];
        }

        $fileSize = filesize($filePath);
        $maxSize = config('filesecurity.clamav.max_file_size', 25 * 1024 * 1024);

        if ($fileSize > $maxSize) {
            Log::warning('ClamAV: file exceeds max scan size, skipping', [
                'file' => $filePath,
                'size' => $fileSize,
                'max' => $maxSize,
            ]);

            return ['clean' => true, 'virus' => null, 'error' => 'File too large for scanning'];
        }

        try {
            $socket = $this->connect();
            $result = $this->sendInstreamCommand($socket, $filePath);
            fclose($socket);

            return $result;
        } catch (\Exception $e) {
            Log::error('ClamAV scan failed', [
                'file' => $filePath,
                'error' => $e->getMessage(),
            ]);

            if (config('filesecurity.clamav.fail_closed', false)) {
                return ['clean' => false, 'virus' => null, 'error' => $e->getMessage()];
            }

            return ['clean' => true, 'virus' => null, 'error' => $e->getMessage()];
        }
    }

    /**
     * Check if the ClamAV daemon is reachable and get its version.
     */
    public function version(): string
    {
        $socket = $this->connect();
        fwrite($socket, "zVERSION\0");

        $response = $this->readResponse($socket);
        fclose($socket);

        return trim($response);
    }

    /**
     * Ping the ClamAV daemon.
     */
    public function ping(): bool
    {
        try {
            $socket = $this->connect();
            fwrite($socket, "zPING\0");

            $response = $this->readResponse($socket);
            fclose($socket);

            return trim($response) === 'PONG';
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Connect to the ClamAV daemon via socket or TCP.
     *
     * @return resource
     */
    private function connect()
    {
        $connectionType = config('filesecurity.clamav.connection_type', 'socket');
        $timeout = config('filesecurity.clamav.timeout', 30);

        if ($connectionType === 'tcp') {
            $host = config('filesecurity.clamav.host', '127.0.0.1');
            $port = config('filesecurity.clamav.port', 3310);
            $socket = @fsockopen($host, $port, $errno, $errstr, $timeout);
        } else {
            $socketPath = config('filesecurity.clamav.socket', '/var/run/clamav/clamd.ctl');
            $socket = @fsockopen("unix://{$socketPath}", -1, $errno, $errstr, $timeout);
        }

        if (! $socket) {
            throw new \RuntimeException("Cannot connect to ClamAV daemon: {$errstr} ({$errno})");
        }

        stream_set_timeout($socket, $timeout);

        return $socket;
    }

    /**
     * Send a file to ClamAV using the INSTREAM command.
     *
     * @param resource $socket
     * @return array{clean: bool, virus: string|null, error: string|null}
     */
    private function sendInstreamCommand($socket, string $filePath): array
    {
        fwrite($socket, "zINSTREAM\0");

        $handle = fopen($filePath, 'rb');
        if (! $handle) {
            throw new \RuntimeException("Cannot open file for scanning: {$filePath}");
        }

        $chunkSize = 8192;
        while (! feof($handle)) {
            $chunk = fread($handle, $chunkSize);
            if ($chunk === false || $chunk === '') {
                break;
            }
            // Send chunk length as 4-byte big-endian unsigned int, then the chunk
            $length = strlen($chunk);
            fwrite($socket, pack('N', $length));
            fwrite($socket, $chunk);
        }
        fclose($handle);

        // Send zero-length chunk to signal end of stream
        fwrite($socket, pack('N', 0));

        $response = $this->readResponse($socket);

        return $this->parseResponse($response);
    }

    /**
     * Read the full response from the ClamAV daemon.
     *
     * @param resource $socket
     */
    private function readResponse($socket): string
    {
        $response = '';
        while (! feof($socket)) {
            $data = fread($socket, 4096);
            if ($data === false || $data === '') {
                break;
            }
            $response .= $data;

            // Null-terminated response
            if (str_contains($data, "\0")) {
                break;
            }
        }

        return rtrim($response, "\0\r\n");
    }

    /**
     * Parse the ClamAV response string.
     *
     * @return array{clean: bool, virus: string|null, error: string|null}
     */
    private function parseResponse(string $response): array
    {
        $response = trim($response);

        // Response format: "stream: OK" or "stream: VirusName FOUND" or "stream: ERROR message"
        if (str_ends_with($response, 'OK')) {
            return ['clean' => true, 'virus' => null, 'error' => null];
        }

        if (str_contains($response, 'FOUND')) {
            // Extract virus name: "stream: VirusName FOUND"
            $virus = trim(str_replace(['stream:', 'FOUND'], '', $response));

            return ['clean' => false, 'virus' => $virus, 'error' => null];
        }

        // Treat any other response as an error
        return ['clean' => false, 'virus' => null, 'error' => "Unexpected ClamAV response: {$response}"];
    }
}
