<?php

namespace Tests\Unit\Services;

use App\Services\DomainRulesCacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class DomainRulesCacheServiceTranslationTest extends TestCase
{
    use RefreshDatabase;

    private DomainRulesCacheService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(DomainRulesCacheService::class);
    }

    /** @test */
    public function it_returns_translated_cache_statistics_in_english()
    {
        App::setLocale('en');
        
        $stats = $this->service->getUserFriendlyCacheStats();
        
        $this->assertArrayHasKey('Cache Key', $stats);
        $this->assertArrayHasKey('Cache TTL (seconds)', $stats);
        $this->assertArrayHasKey('Cache Hit', $stats);
        $this->assertArrayHasKey('Rules Loaded', $stats);
        
        // Check that values are translated
        $this->assertStringContainsString('seconds', $stats['Cache TTL (seconds)']);
        $this->assertContains($stats['Cache Hit'], ['Yes', 'No']);
        $this->assertContains($stats['Rules Loaded'], ['Yes', 'No']);
    }

    /** @test */
    public function it_returns_translated_cache_statistics_in_spanish()
    {
        App::setLocale('es');
        
        $stats = $this->service->getUserFriendlyCacheStats();
        
        $this->assertArrayHasKey('Clave de Caché', $stats);
        $this->assertArrayHasKey('TTL de Caché (segundos)', $stats);
        $this->assertArrayHasKey('Acierto de Caché', $stats);
        $this->assertArrayHasKey('Reglas Cargadas', $stats);
        
        // Check that values are translated to Spanish
        $this->assertStringContainsString('segundos', $stats['TTL de Caché (segundos)']);
        $this->assertContains($stats['Acierto de Caché'], ['Sí', 'No']);
        $this->assertContains($stats['Reglas Cargadas'], ['Sí', 'No']);
    }

    /** @test */
    public function it_returns_translated_cache_statistics_in_french()
    {
        App::setLocale('fr');
        
        $stats = $this->service->getUserFriendlyCacheStats();
        
        $this->assertArrayHasKey('Clé de Cache', $stats);
        $this->assertArrayHasKey('TTL de Cache (secondes)', $stats);
        $this->assertArrayHasKey('Succès de Cache', $stats);
        $this->assertArrayHasKey('Règles Chargées', $stats);
        
        // Check that values are translated to French
        $this->assertStringContainsString('secondes', $stats['TTL de Cache (secondes)']);
        $this->assertContains($stats['Succès de Cache'], ['Oui', 'Non']);
        $this->assertContains($stats['Règles Chargées'], ['Oui', 'Non']);
    }

    /** @test */
    public function it_handles_locale_switching_correctly()
    {
        // Test English
        App::setLocale('en');
        $englishStats = $this->service->getUserFriendlyCacheStats();
        $this->assertArrayHasKey('Cache Key', $englishStats);
        
        // Test Spanish
        App::setLocale('es');
        $spanishStats = $this->service->getUserFriendlyCacheStats();
        $this->assertArrayHasKey('Clave de Caché', $spanishStats);
        
        // Test French
        App::setLocale('fr');
        $frenchStats = $this->service->getUserFriendlyCacheStats();
        $this->assertArrayHasKey('Clé de Cache', $frenchStats);
        
        // Verify they're different
        $this->assertNotEquals($englishStats, $spanishStats);
        $this->assertNotEquals($spanishStats, $frenchStats);
        $this->assertNotEquals($englishStats, $frenchStats);
    }

    protected function tearDown(): void
    {
        // Reset locale to default
        App::setLocale('en');
        parent::tearDown();
    }
}