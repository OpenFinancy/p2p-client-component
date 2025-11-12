<?php

declare(strict_types=1);

namespace OpenFinancy\Component\P2PClient\Validation\Rule;

use OpenFinancy\Component\P2PClient\Configuration\P2PClientConfigurationInterface;
use OpenFinancy\Component\P2PClient\Validation\ConfigurationValidationResult;
use OpenFinancy\Component\P2PClient\Validation\P2PMode;

final class PublicEndpointRule implements ConfigurationValidationRuleInterface
{
    public function supports(): iterable
    {
        return [P2PMode::PEER];
    }

    public function validate(
        P2PClientConfigurationInterface $configuration,
        P2PMode $mode,
        ConfigurationValidationResult $result
    ): void {
        $publicEndpoint = $configuration->getPublicEndpoint();

        if ($publicEndpoint === null || trim($publicEndpoint) === '') {
            $result->addError('Public endpoint must be configured to register as a peer.');
        }
    }
}
