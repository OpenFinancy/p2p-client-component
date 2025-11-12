<?php

declare(strict_types=1);

namespace OpenFinancy\Component\P2PClient\Metadata;

use OpenFinancy\Component\P2PClient\Configuration\P2PClientConfigurationInterface;

final class ConfigurationMetadataProvider implements RegistrationMetadataProviderInterface
{
    /**
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    public function getMetadata(P2PClientConfigurationInterface $configuration, array $context = []): array
    {
        $metadata = $configuration->getMetadata();

        if ($configuration->getPublicEndpoint()) {
            $metadata['public_endpoint'] = $configuration->getPublicEndpoint();
        }

        if ($configuration->shouldAutoApprove()) {
            $metadata['auto_approve'] = true;
        }

        return $metadata;
    }
}
