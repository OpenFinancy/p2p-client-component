<?php

declare(strict_types=1);

namespace OpenFinancy\Component\P2PClient\Tests\Service;

use OpenFinancy\Component\P2PClient\Configuration\P2PClientConfiguration;
use OpenFinancy\Component\P2PClient\Configuration\P2PClientConfigurationProviderInterface;
use OpenFinancy\Component\P2PClient\Metadata\RegistrationMetadataProviderInterface;
use OpenFinancy\Component\P2PClient\Service\P2PClient;
use OpenFinancy\Component\P2PClient\Tests\Support\DummyConfigurationValidator;
use OpenFinancy\Component\P2PClient\Validation\ConfigurationValidationResult;
use OpenFinancy\Component\P2PClient\Validation\Exception\ConfigurationInvalidException;
use OpenFinancy\Component\P2PClient\Validation\P2PMode;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class P2PClientTest extends TestCase
{
    private P2PClientConfiguration $configuration;

    private DummyConfigurationValidator $validator;

    /**
     * @var HttpClientInterface&MockObject
     */
    private HttpClientInterface $httpClient;

    /**
     * @var RegistrationMetadataProviderInterface&MockObject
     */
    private RegistrationMetadataProviderInterface $metadataProvider;

    protected function setUp(): void
    {
        $this->configuration = new P2PClientConfiguration(
            projectType: 'MarketRates',
            providerLabel: 'MarketRates Node',
            hubEndpoint: 'https://hub.test',
            publicEndpoint: 'https://peer.test',
            maxPeerCalls: 3,
            peerEnabled: true,
            providerEnabled: true,
            metadata: ['environment' => 'production']
        );

        $this->validator = new DummyConfigurationValidator();
        $this->validator->setResult(P2PMode::PEER, new ConfigurationValidationResult());
        $this->validator->setResult(P2PMode::PROVIDER, new ConfigurationValidationResult());

        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->metadataProvider = $this->createMock(RegistrationMetadataProviderInterface::class);
    }

    public function testRegisterPeerSubmitsRequestAndReturnsPayload(): void
    {
        $this->metadataProvider
            ->expects(self::once())
            ->method('getMetadata')
            ->with($this->configuration, ['mode' => P2PMode::PEER, 'peer_name' => 'Custom Peer'])
            ->willReturn(['managed_pairs' => ['EUR/USD']]);

        $response = $this->createMock(ResponseInterface::class);
        $response->expects(self::once())->method('getStatusCode')->willReturn(201);
        $response->expects(self::once())->method('toArray')->willReturn([
            'peer' => ['id' => 123, 'status' => 'pending'],
        ]);

        $configurationProvider = $this->createMock(P2PClientConfigurationProviderInterface::class);
        $configurationProvider
            ->expects(self::once())
            ->method('getConfiguration')
            ->willReturn($this->configuration);

        $this->httpClient
            ->expects(self::once())
            ->method('request')
            ->willReturnCallback(function (string $method, string $url, array $options) use ($response): ResponseInterface {
                self::assertSame('POST', $method);
                self::assertSame('https://hub.test/api/peers/register', $url);
                self::assertSame('MarketRates', $options['json']['project_type']);
                self::assertSame('https://peer-endpoint.test', $options['json']['endpoint']);
                self::assertSame(['EUR/USD'], $options['json']['metadata']['managed_pairs']);
                self::assertSame('Custom Peer', $options['json']['metadata']['provider']);
                self::assertSame(30, $options['timeout']);

                return $response;
            });

        $client = new P2PClient(
            $this->httpClient,
            new NullLogger(),
            $configurationProvider,
            $this->validator,
            $this->metadataProvider
        );

        $payload = $client->registerPeer('Custom Peer', 'https://peer-endpoint.test');

        self::assertSame(123, $payload['peer']['id']);
    }

    public function testRegisterPeerThrowsWhenValidationFails(): void
    {
        $result = new ConfigurationValidationResult();
        $result->addError('Missing hub endpoint');

        $this->validator->setResult(P2PMode::PEER, $result);

        // reset expectations for configuration provider to allow additional call
        $configurationProvider = $this->createMock(P2PClientConfigurationProviderInterface::class);
        $configurationProvider
            ->expects(self::once())
            ->method('getConfiguration')
            ->willReturn($this->configuration);

        $client = new P2PClient(
            $this->httpClient,
            new NullLogger(),
            $configurationProvider,
            $this->validator,
            $this->metadataProvider
        );

        $this->expectException(ConfigurationInvalidException::class);

        $client->registerPeer('Peer', 'https://peer-endpoint.test');
    }

    public function testQueryPeersForDataReturnsDataFromSecondPeer(): void
    {
        $discoverResponse = $this->createMock(ResponseInterface::class);
        $discoverResponse->expects(self::once())->method('getStatusCode')->willReturn(200);
        $discoverResponse->expects(self::once())->method('toArray')->willReturn([
            'peers' => [
                ['endpoint' => 'https://peer-one.test', 'metadata' => ['provider' => 'PeerOne']],
                ['endpoint' => 'https://peer-two.test', 'metadata' => ['provider' => 'PeerTwo']],
            ],
        ]);

        $failedPeerResponse = $this->createMock(ResponseInterface::class);
        $failedPeerResponse->expects(self::any())->method('getStatusCode')->willReturn(500);

        $successfulPeerResponse = $this->createMock(ResponseInterface::class);
        $successfulPeerResponse->expects(self::once())->method('getStatusCode')->willReturn(200);
        $successfulPeerResponse->expects(self::once())->method('toArray')->willReturn(['items' => [1, 2, 3]]);

        $configurationProvider = $this->createMock(P2PClientConfigurationProviderInterface::class);
        $configurationProvider
            ->expects(self::once())
            ->method('getConfiguration')
            ->willReturn($this->configuration);

        $this->httpClient
            ->expects(self::atLeast(2))
            ->method('request')
            ->willReturnCallback(function (string $method, string $url, array $options) use ($discoverResponse, $failedPeerResponse, $successfulPeerResponse): ResponseInterface {
                self::assertSame('GET', $method);

                if ($url === 'https://hub.test/api/peers/discover/MarketRates') {
                    self::assertSame('GET', $method);
                    self::assertSame(['timeout' => 30], $options);

                    return $discoverResponse;
                }
                if ($url === 'https://peer-one.test/data') {
                    self::assertSame(
                        [
                            'query' => [],
                            'timeout' => 10,
                            'headers' => ['Accept' => 'application/json'],
                        ],
                        $options
                    );

                    return $failedPeerResponse;
                }
                if ($url === 'https://peer-two.test/data') {
                    self::assertSame(
                        [
                            'query' => [],
                            'timeout' => 10,
                            'headers' => ['Accept' => 'application/json'],
                        ],
                        $options
                    );

                    return $successfulPeerResponse;
                }

                throw new \LogicException(sprintf('Unexpected request URL "%s"', $url));
            });

        $client = new P2PClient(
            $this->httpClient,
            new NullLogger(),
            $configurationProvider,
            $this->validator,
            $this->metadataProvider
        );

        $data = $client->queryPeersForData('/data');

        self::assertSame(['items' => [1, 2, 3]], $data);
    }

    public function testProviderModeEnabledEvenWhenPeerValidationFails(): void
    {
        $configuration = new P2PClientConfiguration(
            projectType: 'MarketRates',
            providerLabel: 'MarketRates Node',
            hubEndpoint: 'https://hub.test',
            publicEndpoint: 'https://peer.test',
            maxPeerCalls: 3,
            peerEnabled: false,
            providerEnabled: true
        );

        $validator = new DummyConfigurationValidator();
        $peerValidation = new ConfigurationValidationResult();
        $peerValidation->addError('Public endpoint missing');
        $validator->setResult(P2PMode::PEER, $peerValidation);
        $validator->setResult(P2PMode::PROVIDER, new ConfigurationValidationResult());

        $configurationProvider = $this->createMock(P2PClientConfigurationProviderInterface::class);
        $configurationProvider
            ->expects(self::once())
            ->method('getConfiguration')
            ->willReturn($configuration);

        $client = new P2PClient(
            $this->httpClient,
            new NullLogger(),
            $configurationProvider,
            $validator,
            $this->metadataProvider
        );

        self::assertFalse($client->isPeerModeEnabled());
        self::assertTrue($client->isProviderModeEnabled());
    }
}
