<?php

declare(strict_types=1);

namespace OpenFinancy\Component\P2PClient\Tests\Service;

use OpenFinancy\Component\P2PClient\Service\PeerProviderInterface;
use OpenFinancy\Component\P2PClient\Service\PeerQueryService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class PeerQueryServiceTest extends TestCase
{
    /**
     * @var PeerProviderInterface&MockObject
     */
    private PeerProviderInterface $peerProvider;

    /**
     * @var HttpClientInterface&MockObject
     */
    private HttpClientInterface $httpClient;

    protected function setUp(): void
    {
        $this->peerProvider = $this->createMock(PeerProviderInterface::class);
        $this->httpClient = $this->createMock(HttpClientInterface::class);
    }

    public function testQueryPeersReturnsFirstSuccessfulResult(): void
    {
        $peers = [
            ['endpoint' => 'https://peer-one.test', 'metadata' => ['provider' => 'PeerOne']],
            ['endpoint' => 'https://peer-two.test', 'metadata' => ['provider' => 'PeerTwo']],
        ];

        $this->peerProvider
            ->expects(self::once())
            ->method('getRandomPeers')
            ->with(null, null, [])
            ->willReturn($peers);

        $failedResponse = $this->createMock(ResponseInterface::class);
        $failedResponse->expects(self::once())->method('getStatusCode')->willReturn(500);

        $successResponse = $this->createMock(ResponseInterface::class);
        $successResponse->expects(self::once())->method('getStatusCode')->willReturn(200);
        $successResponse->expects(self::once())->method('toArray')->willReturn(['items' => [1]]);

        $callIndex = 0;

        $this->httpClient
            ->expects(self::exactly(2))
            ->method('request')
            ->willReturnCallback(function (string $method, string $url, array $options) use (&$callIndex, $failedResponse, $successResponse): ResponseInterface {
                $currentIndex = $callIndex++;

                if ($currentIndex === 0) {
                    self::assertSame('GET', $method);
                    self::assertSame('https://peer-one.test/api/data', $url);
                    self::assertSame(
                        [
                            'query' => [],
                            'timeout' => 10,
                            'headers' => ['Accept' => 'application/json'],
                        ],
                        $options
                    );

                    return $failedResponse;
                }

                if ($currentIndex === 1) {
                    self::assertSame('GET', $method);
                    self::assertSame('https://peer-two.test/api/data', $url);
                    self::assertSame(
                        [
                            'query' => [],
                            'timeout' => 10,
                            'headers' => ['Accept' => 'application/json'],
                        ],
                        $options
                    );

                    return $successResponse;
                }

                throw new \LogicException('Unexpected HTTP call index');
            });

        $service = new PeerQueryService($this->httpClient, new NullLogger(), $this->peerProvider);

        $result = $service->queryPeers('/api/data');

        self::assertNotNull($result);
        self::assertSame(['items' => [1]], $result['data']);
        self::assertSame('PeerTwo', $result['peer']['metadata']['provider']);
    }
}
