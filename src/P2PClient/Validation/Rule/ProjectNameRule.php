<?php

declare(strict_types=1);

namespace OpenFinancy\Component\P2PClient\Validation\Rule;

use OpenFinancy\Component\P2PClient\Configuration\P2PClientConfigurationInterface;
use OpenFinancy\Component\P2PClient\Validation\ConfigurationValidationResult;
use OpenFinancy\Component\P2PClient\Validation\P2PMode;

final class ProjectNameRule implements ConfigurationValidationRuleInterface
{
    public function supports(): iterable
    {
        return [P2PMode::PEER, P2PMode::PROVIDER];
    }

    public function validate(
        P2PClientConfigurationInterface $configuration,
        P2PMode $mode,
        ConfigurationValidationResult $result
    ): void {
        if (trim($configuration->getProjectType()) === '') {
            $result->addError(sprintf('Project name must be configured to run %s operations.', $mode->value));
        }
    }
}
