<?php

declare(strict_types=1);

namespace OpenFinancy\Component\P2PClient\Validation;

use OpenFinancy\Component\P2PClient\Configuration\P2PClientConfigurationInterface;

interface ConfigurationValidatorInterface
{
    public function validate(P2PClientConfigurationInterface $configuration, P2PMode $mode): ConfigurationValidationResult;
}
