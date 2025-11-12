<?php

declare(strict_types=1);

namespace OpenFinancy\Component\P2PClient\Policy;

use OpenFinancy\Component\P2PClient\Configuration\P2PClientConfigurationInterface;

/**
 * Value object encapsulating which P2P roles are allowed given the current configuration.
 */
final class P2PRolePolicy
{
    public function __construct(private readonly P2PClientConfigurationInterface $configuration)
    {
    }

    public function allowsPeerOperations(): bool
    {
        return $this->configuration->isPeerEnabled();
    }

    public function allowsProviderOperations(): bool
    {
        return $this->configuration->isProviderEnabled();
    }

    public function allowsAnyRole(): bool
    {
        return $this->allowsPeerOperations() || $this->allowsProviderOperations();
    }

    public function getConfiguration(): P2PClientConfigurationInterface
    {
        return $this->configuration;
    }
}
