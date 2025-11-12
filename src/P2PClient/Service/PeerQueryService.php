<?php

declare(strict_types=1);

namespace OpenFinancy\Component\P2PClient\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Helper service focused on querying peers for specific data.
 */
final class PeerQueryService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly PeerProviderInterface $peerProvider
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function discoverPeers(): array
    {
        return $this->peerProvider->discoverPeers();
    }

    /**
     * @param array<int|string, scalar|\Stringable|null> $metadataValues
     *
     * @return array<int, array<string, mixed>>
     */
    public function getRandomPeers(?int $limit = null, ?string $metadataKey = null, array $metadataValues = []): array
    {
        return $this->peerProvider->getRandomPeers($limit, $metadataKey, $metadataValues);
    }

    /**
     * @param array<string, mixed> $peer
     * @param array<string, scalar|array<array-key, scalar|\Stringable>> $params
     *
     * @return array<string, mixed>|null
     */
    public function queryPeer(array $peer, string $endpoint, array $params = [], int $timeout = 10): ?array
    {
        $endpointUrl = (string) ($peer['endpoint'] ?? '');

        if ($endpointUrl === '') {
            return null;
        }

        $peerLabel = $peer['metadata']['provider'] ?? ($peer['endpoint'] ?? $peer['id'] ?? 'unknown');
        $url = rtrim($endpointUrl, '/') . '/' . ltrim($endpoint, '/');

        try {
            $this->logger->debug('Querying peer endpoint', [
                'peer_label' => $peerLabel,
                'endpoint' => $url,
                'params' => $params,
            ]);

            $response = $this->httpClient->request('GET', $url, [
                'query' => $params,
                'timeout' => $timeout,
                'headers' => ['Accept' => 'application/json'],
            ]);

            if ($response->getStatusCode() !== 200) {
                return null;
            }

            return $response->toArray();
        } catch (TransportExceptionInterface $exception) {
            $this->logger->warning('Failed to query peer', [
                'peer_label' => $peerLabel,
                'error' => $exception->getMessage(),
            ]);
        } catch (\Throwable $exception) {
            $this->logger->warning('Unexpected error while querying peer', [
                'peer_label' => $peerLabel,
                'error' => $exception->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Query multiple peers sequentially until one returns data.
     *
     * @param array<string, scalar|array<array-key, scalar|\Stringable>> $params
     * @param array<int|string, scalar|\Stringable|null> $metadataValues
     *
     * @return array<string, mixed>|null
     */
    public function queryPeers(string $endpoint, array $params = [], ?int $maxPeers = null, ?string $metadataKey = null, array $metadataValues = []): ?array
    {
        $peers = $this->getRandomPeers($maxPeers, $metadataKey, $metadataValues);

        if ($peers === []) {
            $this->logger->info('No peers available to query');

            return null;
        }

        foreach ($peers as $peer) {
            $data = $this->queryPeer($peer, $endpoint, $params);

            if ($data !== null) {
                return [
                    'data' => $data,
                    'peer' => $peer,
                ];
            }
        }

        $this->logger->info('Peer query exhausted without results', [
            'endpoint' => $endpoint,
            'params' => $params,
            'peers_tried' => count($peers),
        ]);

        return null;
    }
}
