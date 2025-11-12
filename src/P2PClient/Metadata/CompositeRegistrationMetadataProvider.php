<?php

declare(strict_types=1);

namespace OpenFinancy\Component\P2PClient\Metadata;

use OpenFinancy\Component\P2PClient\Configuration\P2PClientConfigurationInterface;

/**
 * Composite metadata provider that merges child provider payloads.
 */
final class CompositeRegistrationMetadataProvider implements RegistrationMetadataProviderInterface
{
    /**
     * @param iterable<RegistrationMetadataProviderInterface> $providers
     */
    public function __construct(
        private readonly iterable $providers,
        private readonly ?MetadataNormalizer $normalizer = null
    ) {
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    public function getMetadata(P2PClientConfigurationInterface $configuration, array $context = []): array
    {
        $metadata = [];

        foreach ($this->providers as $provider) {
            $metadata = array_replace_recursive(
                $metadata,
                $provider->getMetadata($configuration, $context)
            );
        }

        if ($this->normalizer !== null) {
            return $this->normalizer->normalize($metadata);
        }

        return $metadata;
    }
}
