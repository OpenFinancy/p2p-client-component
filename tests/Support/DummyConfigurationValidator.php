<?php

declare(strict_types=1);

namespace OpenFinancy\Component\P2PClient\Tests\Support;

use OpenFinancy\Component\P2PClient\Configuration\P2PClientConfigurationInterface;
use OpenFinancy\Component\P2PClient\Validation\ConfigurationValidationResult;
use OpenFinancy\Component\P2PClient\Validation\ConfigurationValidatorInterface;
use OpenFinancy\Component\P2PClient\Validation\P2PMode;

final class DummyConfigurationValidator implements ConfigurationValidatorInterface
{
    /**
     * @var array<string, ConfigurationValidationResult>
     */
    private array $results = [];

    public function setResult(P2PMode $mode, ConfigurationValidationResult $result): void
    {
        $this->results[$mode->value] = $result;
    }

    public function validate(P2PClientConfigurationInterface $configuration, P2PMode $mode): ConfigurationValidationResult
    {
        return $this->results[$mode->value] ?? new ConfigurationValidationResult();
    }
}
