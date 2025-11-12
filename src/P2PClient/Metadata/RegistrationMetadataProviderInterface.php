<?php

declare(strict_types=1);

namespace OpenFinancy\Component\P2PClient\Metadata;

use OpenFinancy\Component\P2PClient\Configuration\P2PClientConfigurationInterface;

interface RegistrationMetadataProviderInterface
{
    /**
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    public function getMetadata(P2PClientConfigurationInterface $configuration, array $context = []): array;
}
