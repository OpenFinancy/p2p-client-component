# OpenFinancy P2P Client Component

[![CI](https://github.com/openfinancy/p2p-client-component/actions/workflows/ci.yml/badge.svg)](https://github.com/openfinancy/p2p-client-component/actions/workflows/ci.yml)

Shared PHP abstractions for communicating with the OpenFinancy P2P Hub. The component exposes composite building blocks under the `OpenFinancy\Component\P2PClient` namespace so every OpenFinancy service can integrate with the hub without duplicating code.

## Installation

```
composer require openfinancy/p2p-client-component:^0.1
```

This package targets **PHP 8.4+** and leverages the Symfony HTTP client contracts. It can be installed in any Symfony application or standalone PHP project that meets those requirements.

## Key Features

- Immutable configuration DTOs and provider interfaces for plugging into your configuration storage.
- Composite validation rules to guarantee hub/public endpoints and project metadata are present before registration.
- Metadata providers that merge static arrays, callbacks, and configuration-derived metadata into a single hub payload.
- Reusable client and query services for registering peers, handling contributions, and discovering other nodes.

## Quick Start

```php
use OpenFinancy\Component\P2PClient\Configuration\P2PClientConfiguration;
use OpenFinancy\Component\P2PClient\Configuration\P2PClientConfigurationProviderInterface;
use OpenFinancy\Component\P2PClient\Metadata\CompositeRegistrationMetadataProvider;
use OpenFinancy\Component\P2PClient\Metadata\ConfigurationMetadataProvider;
use OpenFinancy\Component\P2PClient\Service\P2PClient;
use OpenFinancy\Component\P2PClient\Service\PeerQueryService;
use OpenFinancy\Component\P2PClient\Validation\CompositeConfigurationValidator;
use OpenFinancy\Component\P2PClient\Validation\Rule\HubEndpointRule;
use OpenFinancy\Component\P2PClient\Validation\Rule\PublicEndpointRule;

$configurationProvider = new class implements P2PClientConfigurationProviderInterface {
    public function getConfiguration(bool $refresh = false): P2PClientConfiguration
    {
        return new P2PClientConfiguration(
            projectType: 'MarketRates',
            providerLabel: 'MarketRates Node',
            hubEndpoint: 'https://hub.openfinancy.io',
            publicEndpoint: 'https://market-rates.example.com',
            maxPeerCalls: 3,
            peerEnabled: true,
            providerEnabled: true,
            metadata: ['environment' => 'production']
        );
    }
};

$metadataProvider = new CompositeRegistrationMetadataProvider([
    new ConfigurationMetadataProvider(),
    // Add project-specific providers here…
]);

$validator = new CompositeConfigurationValidator([
    new HubEndpointRule(),
    new PublicEndpointRule(),
]);

$client = new P2PClient($httpClient, $logger, $configurationProvider, $validator, $metadataProvider);
$client->registerPeer('MarketRates Node', 'https://market-rates.example.com/api/p2p');

$peerQueryService = new PeerQueryService($httpClient, $logger, $client);
$peerQueryService->queryPeers('/api/p2p/currency-pairs', ['baseCurrency' => 'EUR', 'quoteCurrency' => 'USD']);
```

Wire the services through Symfony's DI container or your preferred framework to store configuration, logger, and HTTP client dependencies.

## Documentation

- `CHANGELOG.md` – release history and upgrade notes.
- API usage examples live in the `tests/` and `src/` directories.

## Quality

Run the following commands from the package root to validate changes:

```
composer test
composer analyse
```

The Composer scripts (configured in this repository) execute PHPUnit and static analysis tools to ensure consistent quality before publishing a new release.

## Support

Open an issue at <https://github.com/openfinancy/p2p-client-component/issues> if you need help or want to report a problem.

## License

This package is distributed under the **European Union Public Licence v1.2 (EUPL-1.2)**. See the `LICENSE` file for the full text.
