<?php

namespace Tests\Unit\Services;

use App\Services\ClamAvService;
use Tests\TestCase;

class ClamAvServiceTest extends TestCase
{
    private ClamAvService $clamAvService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clamAvService = new ClamAvService();
    }

    /** @test */
    public function it_skips_scan_when_disabled()
    {
        config(['filesecurity.clamav.enabled' => false]);

        $result = $this->clamAvService->scan('/tmp/any-file.txt');

        $this->assertTrue($result['clean']);
        $this->assertNull($result['virus']);
        $this->assertNull($result['error']);
    }

    /** @test */
    public function it_returns_error_when_file_not_found()
    {
        config(['filesecurity.clamav.enabled' => true]);

        $result = $this->clamAvService->scan('/tmp/nonexistent-file-' . uniqid() . '.txt');

        $this->assertFalse($result['clean']);
        $this->assertEquals('File not found', $result['error']);
    }

    /** @test */
    public function it_skips_files_exceeding_max_size()
    {
        config(['filesecurity.clamav.enabled' => true]);
        config(['filesecurity.clamav.max_file_size' => 10]); // 10 bytes

        $tmpFile = tempnam(sys_get_temp_dir(), 'clamav_test_');
        file_put_contents($tmpFile, str_repeat('A', 100));

        try {
            $result = $this->clamAvService->scan($tmpFile);

            $this->assertTrue($result['clean']);
            $this->assertEquals('File too large for scanning', $result['error']);
        } finally {
            unlink($tmpFile);
        }
    }

    /** @test */
    public function it_fails_open_by_default_when_daemon_unreachable()
    {
        config(['filesecurity.clamav.enabled' => true]);
        config(['filesecurity.clamav.connection_type' => 'tcp']);
        config(['filesecurity.clamav.host' => '127.0.0.1']);
        config(['filesecurity.clamav.port' => 19999]); // unlikely to have anything here
        config(['filesecurity.clamav.timeout' => 1]);
        config(['filesecurity.clamav.fail_closed' => false]);

        $tmpFile = tempnam(sys_get_temp_dir(), 'clamav_test_');
        file_put_contents($tmpFile, 'safe content');

        try {
            $result = $this->clamAvService->scan($tmpFile);

            $this->assertTrue($result['clean']);
            $this->assertNotNull($result['error']);
        } finally {
            unlink($tmpFile);
        }
    }

    /** @test */
    public function it_fails_closed_when_configured()
    {
        config(['filesecurity.clamav.enabled' => true]);
        config(['filesecurity.clamav.connection_type' => 'tcp']);
        config(['filesecurity.clamav.host' => '127.0.0.1']);
        config(['filesecurity.clamav.port' => 19999]);
        config(['filesecurity.clamav.timeout' => 1]);
        config(['filesecurity.clamav.fail_closed' => true]);

        $tmpFile = tempnam(sys_get_temp_dir(), 'clamav_test_');
        file_put_contents($tmpFile, 'safe content');

        try {
            $result = $this->clamAvService->scan($tmpFile);

            $this->assertFalse($result['clean']);
            $this->assertNotNull($result['error']);
        } finally {
            unlink($tmpFile);
        }
    }

    /** @test */
    public function it_returns_false_ping_when_daemon_unreachable()
    {
        config(['filesecurity.clamav.connection_type' => 'tcp']);
        config(['filesecurity.clamav.host' => '127.0.0.1']);
        config(['filesecurity.clamav.port' => 19999]);
        config(['filesecurity.clamav.timeout' => 1]);

        $this->assertFalse($this->clamAvService->ping());
    }
}
