<?php

declare(strict_types=1);

namespace OpenFinancy\Component\P2PClient\Configuration;

enum ProjectType: string
{
    case MARKET_RATES = 'MarketRates';
    case CRYPTO_MARKET = 'CryptoMarket';
    case METAL_RATES = 'MetalRates';
    case COLLECTIBLE_RATES = 'CollectibleRates';

    public function label(): string
    {
        return match ($this) {
            self::MARKET_RATES => 'Market Rates',
            self::CRYPTO_MARKET => 'Crypto Market',
            self::METAL_RATES => 'Metal Rates',
            self::COLLECTIBLE_RATES => 'Collectible Rates',
        };
    }

    public static function fromString(?string $value): self
    {
        if ($value === null || trim($value) === '') {
            throw new \InvalidArgumentException('Project type value must not be empty.');
        }

        foreach (self::cases() as $case) {
            if (strcasecmp($case->value, $value) === 0) {
                return $case;
            }
        }

        throw new \InvalidArgumentException(sprintf('Unknown project type "%s".', $value));
    }
}
