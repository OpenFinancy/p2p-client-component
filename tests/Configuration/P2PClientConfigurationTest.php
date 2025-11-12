<?php

declare(strict_types=1);

namespace OpenFinancy\Component\P2PClient\Tests\Configuration;

use OpenFinancy\Component\P2PClient\Configuration\P2PClientConfiguration;
use PHPUnit\Framework\TestCase;

final class P2PClientConfigurationTest extends TestCase
{
    public function testConfigurationExposesImmutableData(): void
    {
        $configuration = new P2PClientConfiguration(
            projectName: 'MarketRates',
            providerLabel: 'MarketRates Node',
            hubEndpoint: 'https://hub.test',
            publicEndpoint: 'https://peer.test',
            maxPeerCalls: 5,
            peerEnabled: true,
            providerEnabled: false,
            autoApprove: true,
            metadata: ['environment' => 'qa']
        );

        self::assertSame('MarketRates', $configuration->getProjectName());
        self::assertSame('MarketRates Node', $configuration->getProviderLabel());
        self::assertSame('https://hub.test', $configuration->getHubEndpoint());
        self::assertSame('https://peer.test', $configuration->getPublicEndpoint());
        self::assertSame(5, $configuration->getMaxPeerCalls());
        self::assertTrue($configuration->isPeerEnabled());
        self::assertFalse($configuration->isProviderEnabled());
        self::assertTrue($configuration->shouldAutoApprove());
        self::assertSame(['environment' => 'qa'], $configuration->getMetadata());
    }

    public function testWithOverridesReturnsNewInstance(): void
    {
        $configuration = new P2PClientConfiguration(
            projectName: 'MarketRates',
            providerLabel: 'MarketRates Node',
            hubEndpoint: 'https://hub.test'
        );

        $overridden = $configuration->withOverrides(
            projectName: 'CollectibleRates',
            maxPeerCalls: 1,
            peerEnabled: true
        );

        self::assertNotSame($configuration, $overridden);
        self::assertSame('MarketRates', $configuration->getProjectName());
        self::assertSame('CollectibleRates', $overridden->getProjectName());
        self::assertSame(1, $overridden->getMaxPeerCalls());
        self::assertTrue($overridden->isPeerEnabled());
    }
}
