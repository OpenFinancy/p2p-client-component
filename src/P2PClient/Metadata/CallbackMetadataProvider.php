<?php

declare(strict_types=1);

namespace OpenFinancy\Component\P2PClient\Metadata;

use OpenFinancy\Component\P2PClient\Configuration\P2PClientConfigurationInterface;

/**
 * Defers metadata production to a user-supplied callback.
 */
final class CallbackMetadataProvider implements RegistrationMetadataProviderInterface
{
    /**
     * @var callable(P2PClientConfigurationInterface, array<string, mixed>): array<string, mixed>
     */
    private $callback;

    /**
     * @param callable(P2PClientConfigurationInterface, array<string, mixed>): array<string, mixed> $callback
     */
    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    public function getMetadata(P2PClientConfigurationInterface $configuration, array $context = []): array
    {
        return ($this->callback)($configuration, $context);
    }
}
