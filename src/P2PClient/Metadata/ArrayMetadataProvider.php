<?php

declare(strict_types=1);

namespace OpenFinancy\Component\P2PClient\Metadata;

use OpenFinancy\Component\P2PClient\Configuration\P2PClientConfigurationInterface;

/**
 * Returns metadata from a predefined array.
 */
final class ArrayMetadataProvider implements RegistrationMetadataProviderInterface
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(private readonly array $metadata)
    {
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    public function getMetadata(P2PClientConfigurationInterface $configuration, array $context = []): array
    {
        return $this->metadata;
    }
}
