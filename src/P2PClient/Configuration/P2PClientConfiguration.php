<?php

declare(strict_types=1);

namespace OpenFinancy\Component\P2PClient\Configuration;

/**
 * Default immutable configuration implementation.
 */
final class P2PClientConfiguration implements P2PClientConfigurationInterface
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        private readonly string $projectType,
        private readonly string $providerLabel,
        private readonly string $hubEndpoint,
        private readonly ?string $publicEndpoint = null,
        private readonly int $maxPeerCalls = 3,
        private readonly bool $peerEnabled = false,
        private readonly bool $providerEnabled = false,
        private readonly bool $autoApprove = false,
        private readonly array $metadata = [],
    ) {
    }

    public function getProjectType(): string
    {
        return $this->projectType;
    }

    public function getProviderLabel(): string
    {
        return $this->providerLabel;
    }

    public function getHubEndpoint(): string
    {
        return $this->hubEndpoint;
    }

    public function getPublicEndpoint(): ?string
    {
        return $this->publicEndpoint !== null ? trim($this->publicEndpoint) : null;
    }

    public function getMaxPeerCalls(): int
    {
        return max(1, $this->maxPeerCalls);
    }

    public function isPeerEnabled(): bool
    {
        return $this->peerEnabled;
    }

    public function isProviderEnabled(): bool
    {
        return $this->providerEnabled;
    }

    public function shouldAutoApprove(): bool
    {
        return $this->autoApprove;
    }

    /**
     * @return array<string, mixed>
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * @param array<string, mixed>|null $metadata
     */
    public function withOverrides(
        ?string $projectType = null,
        ?string $providerLabel = null,
        ?string $hubEndpoint = null,
        ?string $publicEndpoint = null,
        ?int $maxPeerCalls = null,
        ?bool $peerEnabled = null,
        ?bool $providerEnabled = null,
        ?bool $autoApprove = null,
        ?array $metadata = null,
    ): self {
        return new self(
            projectType: $projectType ?? $this->projectType,
            providerLabel: $providerLabel ?? $this->providerLabel,
            hubEndpoint: $hubEndpoint ?? $this->hubEndpoint,
            publicEndpoint: $publicEndpoint ?? $this->publicEndpoint,
            maxPeerCalls: $maxPeerCalls ?? $this->maxPeerCalls,
            peerEnabled: $peerEnabled ?? $this->peerEnabled,
            providerEnabled: $providerEnabled ?? $this->providerEnabled,
            autoApprove: $autoApprove ?? $this->autoApprove,
            metadata: $metadata ?? $this->metadata,
        );
    }
}
