<?php

declare(strict_types=1);

namespace OpenFinancy\Component\P2PClient\Configuration;

/**
 * Lightweight array-based configuration provider useful for tests and simple integrations.
 */
final class ArrayConfigurationProvider implements P2PClientConfigurationProviderInterface
{
    /**
     * @param array<string, mixed> $options
     */
    public function __construct(private readonly array $options)
    {
    }

    public function getConfiguration(bool $refresh = false): P2PClientConfigurationInterface
    {
        $metadata = $this->options['metadata'] ?? [];

        if (!is_array($metadata)) {
            $metadata = [];
        }

        return new P2PClientConfiguration(
            projectName: (string) ($this->options['project_name'] ?? $this->options['projectName'] ?? ''),
            providerLabel: (string) ($this->options['provider_label'] ?? $this->options['providerLabel'] ?? ''),
            hubEndpoint: (string) ($this->options['hub_endpoint'] ?? $this->options['hubEndpoint'] ?? ''),
            publicEndpoint: isset($this->options['public_endpoint']) ? (string) $this->options['public_endpoint'] : ($this->options['publicEndpoint'] ?? null),
            maxPeerCalls: isset($this->options['max_peer_calls']) ? (int) $this->options['max_peer_calls'] : (int) ($this->options['maxPeerCalls'] ?? 3),
            peerEnabled: (bool) ($this->options['peer_enabled'] ?? $this->options['peerEnabled'] ?? false),
            providerEnabled: (bool) ($this->options['provider_enabled'] ?? $this->options['providerEnabled'] ?? false),
            autoApprove: (bool) ($this->options['auto_approve'] ?? $this->options['autoApprove'] ?? false),
            metadata: $metadata,
        );
    }
}
