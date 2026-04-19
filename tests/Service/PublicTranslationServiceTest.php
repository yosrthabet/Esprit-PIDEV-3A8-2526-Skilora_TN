<?php

namespace App\Tests\Service;

use App\Service\PublicTranslationService;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class PublicTranslationServiceTest extends TestCase
{
    public function testServiceInstantiates(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $service = new PublicTranslationService($httpClient);

        $this->assertInstanceOf(PublicTranslationService::class, $service);
    }
}
