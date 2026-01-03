<?php

declare(strict_types=1);

namespace OpenFinancy\Component\P2PClient\Tests\Validation;

use OpenFinancy\Component\P2PClient\Configuration\P2PClientConfiguration;
use OpenFinancy\Component\P2PClient\Validation\CompositeConfigurationValidator;
use OpenFinancy\Component\P2PClient\Validation\P2PMode;
use OpenFinancy\Component\P2PClient\Validation\Rule\HubEndpointRule;
use OpenFinancy\Component\P2PClient\Validation\Rule\ProjectNameRule;
use OpenFinancy\Component\P2PClient\Validation\Rule\ProviderLabelRule;
use OpenFinancy\Component\P2PClient\Validation\Rule\PublicEndpointRule;
use PHPUnit\Framework\TestCase;

final class CompositeConfigurationValidatorTest extends TestCase
{
    public function testValidatorReportsErrorsForMissingFields(): void
    {
        $configuration = new P2PClientConfiguration(
            projectType: '',
            providerLabel: '',
            hubEndpoint: '',
            publicEndpoint: null,
            peerEnabled: true,
            providerEnabled: true
        );

        $validator = new CompositeConfigurationValidator([
            new HubEndpointRule(),
            new ProjectNameRule(),
            new ProviderLabelRule(),
            new PublicEndpointRule(),
        ]);

        $peerResult = $validator->validate($configuration, P2PMode::PEER);

        self::assertTrue($peerResult->hasErrors());
        self::assertCount(4, $peerResult->getErrors(), 'Peer validation should report hub endpoint, project name, provider label, and public endpoint errors');

        $providerResult = $validator->validate($configuration, P2PMode::PROVIDER);

        self::assertTrue($providerResult->hasErrors());
        self::assertCount(3, $providerResult->getErrors(), 'Provider validation should report hub endpoint, project name, provider label errors');
    }

    public function testValidatorPassesForValidConfiguration(): void
    {
        $configuration = new P2PClientConfiguration(
            projectType: 'MarketRates',
            providerLabel: 'MarketRates Node',
            hubEndpoint: 'https://hub.test',
            publicEndpoint: 'https://peer.test'
        );

        $validator = new CompositeConfigurationValidator([
            new HubEndpointRule(),
            new ProjectNameRule(),
            new ProviderLabelRule(),
            new PublicEndpointRule(),
        ]);

        $peerResult = $validator->validate($configuration, P2PMode::PEER);
        self::assertFalse($peerResult->hasErrors());

        $providerResult = $validator->validate($configuration, P2PMode::PROVIDER);
        self::assertFalse($providerResult->hasErrors());
    }
}
