<?php

declare(strict_types=1);

namespace OpenFinancy\Component\P2PClient\Service;

interface PeerProviderInterface
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function discoverPeers(): array;

    /**
     * @param array<int|string, scalar|\Stringable|null> $metadataValues
     *
     * @return array<int, array<string, mixed>>
     */
    public function getRandomPeers(?int $limit = null, ?string $metadataKey = null, array $metadataValues = []): array;
}
