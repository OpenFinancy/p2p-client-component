<?php

declare(strict_types=1);

namespace OpenFinancy\Component\P2PClient\Configuration;

/**
 * Abstraction allowing projects to plug their own configuration source (settings manager, env vars, etc.).
 */
interface P2PClientConfigurationProviderInterface
{
    public function getConfiguration(bool $refresh = false): P2PClientConfigurationInterface;
}
