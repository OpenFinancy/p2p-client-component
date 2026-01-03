<?php

declare(strict_types=1);

namespace OpenFinancy\Component\P2PClient\Tests\Metadata;

use OpenFinancy\Component\P2PClient\Configuration\P2PClientConfiguration;
use OpenFinancy\Component\P2PClient\Metadata\ArrayMetadataProvider;
use OpenFinancy\Component\P2PClient\Metadata\CompositeRegistrationMetadataProvider;
use OpenFinancy\Component\P2PClient\Metadata\ConfigurationMetadataProvider;
use OpenFinancy\Component\P2PClient\Metadata\MetadataNormalizer;
use OpenFinancy\Component\P2PClient\Metadata\RegistrationMetadataProviderInterface;
use PHPUnit\Framework\TestCase;

final class CompositeRegistrationMetadataProviderTest extends TestCase
{
    public function testCompositeMergesProvidersAndNormalizes(): void
    {
        $configuration = new P2PClientConfiguration(
            projectType: 'MarketRates',
            providerLabel: 'MarketRates Node',
            hubEndpoint: 'https://hub.test',
            publicEndpoint: 'https://peer.test',
            metadata: [
                'managed_pairs' => ['EUR/USD', 'eur/usd'],
                'tags' => ['forex', ' FOREX '],
            ]
        );

        $providers = [
            new ConfigurationMetadataProvider(),
            new ArrayMetadataProvider([
                'environment' => 'production',
                'managed_pairs' => ['EUR/USD', 'GBP/USD'],
            ]),
            new class () implements RegistrationMetadataProviderInterface {
                public function getMetadata(\OpenFinancy\Component\P2PClient\Configuration\P2PClientConfigurationInterface $configuration, array $context = []): array
                {
                    return [
                        'custom' => [
                            'version' => '1.0.0 ',
                        ],
                    ];
                }
            },
        ];

        $composite = new CompositeRegistrationMetadataProvider($providers, new MetadataNormalizer());

        $metadata = $composite->getMetadata($configuration, ['mode' => 'peer']);

        self::assertSame('production', $metadata['environment']);
        self::assertSame(['EUR/USD', 'GBP/USD'], $metadata['managed_pairs']);
        self::assertSame('https://peer.test', $metadata['public_endpoint']);
        self::assertSame('1.0.0', $metadata['custom']['version']);
        self::assertSame(['forex', 'FOREX'], $metadata['tags']);
    }
}
