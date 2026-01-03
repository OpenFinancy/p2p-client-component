<?php

declare(strict_types=1);

namespace OpenFinancy\Component\P2PClient\Service;

use OpenFinancy\Component\P2PClient\Configuration\P2PClientConfigurationInterface;
use OpenFinancy\Component\P2PClient\Configuration\P2PClientConfigurationProviderInterface;
use OpenFinancy\Component\P2PClient\Metadata\RegistrationMetadataProviderInterface;
use OpenFinancy\Component\P2PClient\Policy\P2PRolePolicy;
use OpenFinancy\Component\P2PClient\Validation\ConfigurationValidatorInterface;
use OpenFinancy\Component\P2PClient\Validation\Exception\ConfigurationInvalidException;
use OpenFinancy\Component\P2PClient\Validation\P2PMode;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Shared service implementing OpenFinancy P2P hub workflows.
 */
final class P2PClient implements PeerProviderInterface
{
    private ?P2PClientConfigurationInterface $cachedConfiguration = null;
    private ?P2PRolePolicy $cachedRolePolicy = null;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly P2PClientConfigurationProviderInterface $configurationProvider,
        private readonly ConfigurationValidatorInterface $validator,
        private readonly RegistrationMetadataProviderInterface $metadataProvider,
    ) {
    }

    public function refreshConfiguration(): void
    {
        $this->cachedConfiguration = $this->configurationProvider->getConfiguration(true);
        $this->cachedRolePolicy = new P2PRolePolicy($this->cachedConfiguration);
    }

    public function isPeerModeEnabled(): bool
    {
        if (!$this->getRolePolicy()->allowsPeerOperations()) {
            return false;
        }

        return $this->isConfigurationReady(P2PMode::PEER, false);
    }

    public function isProviderModeEnabled(): bool
    {
        if (!$this->getRolePolicy()->allowsProviderOperations()) {
            return false;
        }

        return $this->isConfigurationReady(P2PMode::PROVIDER, false);
    }

    public function getHubEndpoint(): ?string
    {
        $endpoint = trim($this->getConfiguration()->getHubEndpoint());

        return $endpoint !== '' ? $endpoint : null;
    }

    public function getProjectType(): string
    {
        return $this->getConfiguration()->getProjectType();
    }

    public function getMaxPeerCalls(): int
    {
        return $this->getConfiguration()->getMaxPeerCalls();
    }

    /**
     * @param array<string, mixed>|null $metadata
     *
     * @return array<string, mixed>
     *
     * @throws ConfigurationInvalidException
     */
    public function registerPeer(string $peerName, string $endpoint, ?array $metadata = null): array
    {
        $configuration = $this->requireModeReady(P2PMode::PEER);

        try {
            $payloadMetadata = $this->buildRegistrationMetadata($configuration, $peerName, $metadata ?? []);

            $response = $this->httpClient->request('POST', $this->buildUrl($configuration->getHubEndpoint(), '/api/peers/register'), [
                'json' => [
                    'project_type' => $configuration->getProjectType(),
                    'endpoint' => $endpoint,
                    'metadata' => $payloadMetadata,
                ],
                'timeout' => 30,
            ]);

            $data = $this->expectStatus($response, 201, 'register peer');

            $this->logger->info('Successfully registered as peer with P2P Hub', [
                'peer_id' => $data['peer']['id'] ?? null,
                'status' => $data['peer']['status'] ?? null,
            ]);

            return $data;
        } catch (\Throwable $exception) {
            $this->logger->error('Failed to register peer with P2P Hub', [
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed>|null $previousData
     *
     * @return array<string, mixed>
     *
     * @throws ConfigurationInvalidException
     */
    public function submitContribution(
        string $entityType,
        string $operation,
        array $data,
        ?string $entityId = null,
        ?array $previousData = null
    ): array {
        $configuration = $this->requireModeReady(P2PMode::PEER);

        try {
            $response = $this->httpClient->request('POST', $this->buildUrl($configuration->getHubEndpoint(), '/api/contributions/submit'), [
                'json' => [
                    'entity_type' => $entityType,
                    'operation' => $operation,
                    'data' => $data,
                    'entity_id' => $entityId,
                    'previous_data' => $previousData,
                ],
                'headers' => $this->buildJsonHeaders(),
                'timeout' => 30,
            ]);

            $payload = $this->expectStatus($response, 201, 'submit contribution');

            $this->logger->info('Successfully submitted contribution to P2P Hub', [
                'contribution_id' => $payload['contribution']['id'] ?? null,
                'status' => $payload['contribution']['status'] ?? null,
            ]);

            return $payload;
        } catch (\Throwable $exception) {
            $this->logger->error('Failed to submit contribution to P2P Hub', [
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    /**
     * @return array<string, mixed>
     *
     * @throws ConfigurationInvalidException
     */
    public function submitApproval(string $contributionId, string $decision, ?string $comment = null): array
    {
        $configuration = $this->requireModeReady(P2PMode::PEER);

        try {
            $response = $this->httpClient->request('POST', $this->buildUrl($configuration->getHubEndpoint(), '/api/approvals/submit'), [
                'json' => [
                    'contribution_id' => $contributionId,
                    'decision' => $decision,
                    'comment' => $comment,
                ],
                'headers' => $this->buildJsonHeaders(),
                'timeout' => 30,
            ]);

            $payload = $this->expectStatus($response, 201, 'submit approval');

            $this->logger->info('Successfully submitted approval to P2P Hub', [
                'approval_id' => $payload['approval']['id'] ?? null,
                'decision' => $decision,
            ]);

            return $payload;
        } catch (\Throwable $exception) {
            $this->logger->error('Failed to submit approval to P2P Hub', [
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getPendingContributions(): array
    {
        if (!$this->isPeerModeEnabled()) {
            return [];
        }

        try {
            $configuration = $this->getConfiguration();
            $response = $this->httpClient->request('GET', $this->buildUrl($configuration->getHubEndpoint(), '/api/contributions/pending'), [
                'timeout' => 30,
            ]);

            if ($response->getStatusCode() === 200) {
                $payload = $response->toArray();

                return $payload['contributions'] ?? [];
            }
        } catch (TransportExceptionInterface $exception) {
            $this->logger->error('Failed to fetch pending contributions', [
                'error' => $exception->getMessage(),
            ]);
        } catch (\Throwable $exception) {
            $this->logger->error('Unexpected error while fetching pending contributions', [
                'error' => $exception->getMessage(),
            ]);
        }

        return [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function discoverPeers(): array
    {
        if (!$this->isProviderModeEnabled()) {
            return [];
        }

        try {
            $configuration = $this->getConfiguration();
            $response = $this->httpClient->request(
                'GET',
                $this->buildUrl($configuration->getHubEndpoint(), '/api/peers/discover/' . $configuration->getProjectType()),
                ['timeout' => 30]
            );

            if ($response->getStatusCode() === 200) {
                $payload = $response->toArray();

                return $payload['peers'] ?? [];
            }
        } catch (TransportExceptionInterface $exception) {
            $this->logger->error('Failed to discover peers', [
                'error' => $exception->getMessage(),
            ]);
        } catch (\Throwable $exception) {
            $this->logger->error('Unexpected error while discovering peers', [
                'error' => $exception->getMessage(),
            ]);
        }

        return [];
    }

    /**
     * @param array<int|string, scalar|\Stringable|null> $values
     *
     * @return array<int, array<string, mixed>>
     */
    public function searchPeersByMetadata(string $metadataKey, array $values): array
    {
        if (!$this->isProviderModeEnabled()) {
            return [];
        }

        $metadataKey = trim($metadataKey);

        if ($metadataKey === '') {
            return [];
        }

        $normalizedValues = array_values(array_unique(array_filter(array_map(
            static function ($value): string {
                if ($value instanceof \Stringable || is_scalar($value)) {
                    return trim((string) $value);
                }

                return '';
            },
            $values
        ), static fn (string $value): bool => $value !== '')));

        if ($normalizedValues === []) {
            return [];
        }

        try {
            $configuration = $this->getConfiguration();

            $response = $this->httpClient->request(
                'GET',
                $this->buildUrl($configuration->getHubEndpoint(), '/api/peers/search/' . $configuration->getProjectType()),
                [
                    'query' => [
                        'metadata_key' => $metadataKey,
                        'metadata_values' => $normalizedValues,
                    ],
                    'timeout' => 30,
                ]
            );

            if ($response->getStatusCode() === 200) {
                $payload = $response->toArray();

                return $payload['peers'] ?? [];
            }
        } catch (TransportExceptionInterface $exception) {
            $this->logger->warning('Failed to search peers by metadata', [
                'metadata_key' => $metadataKey,
                'values' => $normalizedValues,
                'error' => $exception->getMessage(),
            ]);
        } catch (\Throwable $exception) {
            $this->logger->warning('Unexpected error during peer metadata search', [
                'metadata_key' => $metadataKey,
                'values' => $normalizedValues,
                'error' => $exception->getMessage(),
            ]);
        }

        return [];
    }

    /**
     * @param array<int|string, scalar|\Stringable|null> $metadataValues
     *
     * @return array<int, array<string, mixed>>
     */
    public function getRandomPeers(?int $limit = null, ?string $metadataKey = null, array $metadataValues = []): array
    {
        if (!$this->isProviderModeEnabled()) {
            return [];
        }

        $peers = $metadataKey !== null
            ? $this->searchPeersByMetadata($metadataKey, $metadataValues)
            : $this->discoverPeers();

        if ($peers === []) {
            return [];
        }

        shuffle($peers);

        $limit ??= $this->getConfiguration()->getMaxPeerCalls();

        return array_slice($peers, 0, $limit);
    }

    /**
     * @param array<string, scalar|array<array-key, scalar>> $params
     *
     * @return array<string, mixed>|null
     */
    public function queryPeersForData(string $endpoint, array $params = []): ?array
    {
        $peers = $this->getRandomPeers();

        if ($peers === []) {
            $this->logger->info('No peers available to query');

            return null;
        }

        foreach ($peers as $peer) {
            $endpointBase = (string) ($peer['endpoint'] ?? '');

            if ($endpointBase === '') {
                continue;
            }

            $peerLabel = $peer['metadata']['provider'] ?? ($peer['endpoint'] ?? $peer['id'] ?? 'unknown');

            try {
                $url = $this->buildUrl($endpointBase, $endpoint);

                $this->logger->debug('Querying peer for data', [
                    'peer_label' => $peerLabel,
                    'endpoint' => $url,
                ]);

                $response = $this->httpClient->request('GET', $url, [
                    'query' => $params,
                    'timeout' => 10,
                    'headers' => ['Accept' => 'application/json'],
                ]);

                if ($response->getStatusCode() === 200) {
                    $data = $response->toArray();

                    $this->logger->info('Successfully retrieved data from peer', [
                        'peer_label' => $peerLabel,
                    ]);

                    return $data;
                }
            } catch (TransportExceptionInterface $exception) {
                $this->logger->warning('Failed to query peer', [
                    'peer_label' => $peerLabel,
                    'error' => $exception->getMessage(),
                ]);
            } catch (\Throwable $exception) {
                $this->logger->warning('Unexpected error when querying peer', [
                    'peer_label' => $peerLabel,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        $this->logger->warning('All peer queries failed', [
            'peers_tried' => count($peers),
        ]);

        return null;
    }

    /**
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    private function buildRegistrationMetadata(
        P2PClientConfigurationInterface $configuration,
        string $peerName,
        array $overrides
    ): array {
        $baseMetadata = $this->metadataProvider->getMetadata($configuration, [
            'mode' => P2PMode::PEER,
            'peer_name' => $peerName,
        ]);

        return array_replace_recursive(
            $baseMetadata,
            ['provider' => $peerName !== '' ? $peerName : $configuration->getProviderLabel()],
            $overrides
        );
    }

    private function buildUrl(string $baseUrl, string $path): string
    {
        return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
    }

    /**
     * @return array<string, string>
     */
    private function buildJsonHeaders(): array
    {
        return ['Content-Type' => 'application/json'];
    }

    /**
     * @throws ConfigurationInvalidException
     */
    private function requireModeReady(P2PMode $mode): P2PClientConfigurationInterface
    {
        $configuration = $this->getConfiguration();
        $policy = $this->getRolePolicy();

        $isAllowed = match ($mode) {
            P2PMode::PEER => $policy->allowsPeerOperations(),
            P2PMode::PROVIDER => $policy->allowsProviderOperations(),
        };

        if (!$isAllowed) {
            throw new \RuntimeException(sprintf('P2P %s mode is not enabled for this project', $mode->value));
        }

        $result = $this->validator->validate($configuration, $mode);

        if ($result->hasErrors()) {
            foreach ($result->getErrors() as $error) {
                $this->logger->warning('P2P configuration error', [
                    'mode' => $mode->value,
                    'message' => $error,
                ]);
            }

            throw new ConfigurationInvalidException($mode, $result);
        }

        if ($result->hasWarnings()) {
            foreach ($result->getWarnings() as $warning) {
                $this->logger->warning('P2P configuration warning', [
                    'mode' => $mode->value,
                    'message' => $warning,
                ]);
            }
        }

        return $configuration;
    }

    private function getConfiguration(): P2PClientConfigurationInterface
    {
        if ($this->cachedConfiguration === null) {
            $this->cachedConfiguration = $this->configurationProvider->getConfiguration();
        }

        return $this->cachedConfiguration;
    }

    private function getRolePolicy(): P2PRolePolicy
    {
        if ($this->cachedRolePolicy === null) {
            $this->cachedRolePolicy = new P2PRolePolicy($this->getConfiguration());
        }

        return $this->cachedRolePolicy;
    }

    private function isConfigurationReady(P2PMode $mode, bool $log = true): bool
    {
        $configuration = $this->getConfiguration();
        $result = $this->validator->validate($configuration, $mode);

        if ($result->hasErrors()) {
            if ($log) {
                foreach ($result->getErrors() as $error) {
                    $this->logger->warning('P2P configuration error', [
                        'mode' => $mode->value,
                        'message' => $error,
                    ]);
                }
            }

            return false;
        }

        if ($log && $result->hasWarnings()) {
            foreach ($result->getWarnings() as $warning) {
                $this->logger->warning('P2P configuration warning', [
                    'mode' => $mode->value,
                    'message' => $warning,
                ]);
            }
        }

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    private function expectStatus(ResponseInterface $response, int $expectedStatus, string $operation): array
    {
        $status = $response->getStatusCode();

        if ($status !== $expectedStatus) {
            throw new \RuntimeException(sprintf(
                'Unexpected response status (%d) while attempting to %s',
                $status,
                $operation
            ));
        }

        return $response->toArray();
    }
}
