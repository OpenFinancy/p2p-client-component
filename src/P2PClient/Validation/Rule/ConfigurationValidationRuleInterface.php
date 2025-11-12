<?php

declare(strict_types=1);

namespace OpenFinancy\Component\P2PClient\Validation\Rule;

use OpenFinancy\Component\P2PClient\Configuration\P2PClientConfigurationInterface;
use OpenFinancy\Component\P2PClient\Validation\ConfigurationValidationResult;
use OpenFinancy\Component\P2PClient\Validation\P2PMode;

interface ConfigurationValidationRuleInterface
{
    /**
     * @return iterable<P2PMode>
     */
    public function supports(): iterable;

    public function validate(
        P2PClientConfigurationInterface $configuration,
        P2PMode $mode,
        ConfigurationValidationResult $result
    ): void;
}
