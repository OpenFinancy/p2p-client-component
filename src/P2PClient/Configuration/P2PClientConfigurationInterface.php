<?php

declare(strict_types=1);

namespace OpenFinancy\Component\P2PClient\Configuration;

/**
 * Immutable view of the P2P client configuration required by the shared services.
 */
interface P2PClientConfigurationInterface
{
    public function getProjectName(): string;

    public function getProviderLabel(): string;

    public function getHubEndpoint(): string;

    public function getPublicEndpoint(): ?string;

    public function getMaxPeerCalls(): int;

    public function isPeerEnabled(): bool;

    public function isProviderEnabled(): bool;

    public function shouldAutoApprove(): bool;

    /**
     * @return array<string, mixed>
     */
    public function getMetadata(): array;
}
