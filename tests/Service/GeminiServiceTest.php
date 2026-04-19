<?php

namespace App\Tests\Service;

use App\Service\GeminiService;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GeminiServiceTest extends TestCase
{
    public function testSuggestSubjectReturnsNullWithoutApiKey(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        // no API calls should be made
        $httpClient->expects($this->never())->method('request');

        $service = new GeminiService($httpClient, '');
        $result = $service->suggestSubject('I cannot log in to my account');

        $this->assertNull($result);
    }

    public function testCorrectTextReturnsNullWithoutApiKey(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects($this->never())->method('request');

        $service = new GeminiService($httpClient, '');
        $result = $service->correctText('This text has errrors');

        $this->assertNull($result);
    }

    public function testDetectToneReturnsNeutralWithoutApiKey(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects($this->never())->method('request');

        $service = new GeminiService($httpClient, '');
        $result = $service->detectTone('I am very frustrated with this service');

        // detectTone falls back to 'Neutral' when API is unavailable
        $this->assertSame('Neutral', $result);
    }

    public function testTranslateReturnsNullWithoutApiKey(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects($this->never())->method('request');

        $service = new GeminiService($httpClient, '');
        $result = $service->translate('Hello', 'fr');

        $this->assertNull($result);
    }

    public function testServiceInstantiatesWithApiKey(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $service = new GeminiService($httpClient, 'test-key-123');

        $this->assertInstanceOf(GeminiService::class, $service);
    }
}
