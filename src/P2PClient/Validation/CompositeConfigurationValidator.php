<?php

declare(strict_types=1);

namespace OpenFinancy\Component\P2PClient\Validation;

use OpenFinancy\Component\P2PClient\Configuration\P2PClientConfigurationInterface;
use OpenFinancy\Component\P2PClient\Validation\Rule\ConfigurationValidationRuleInterface;

/**
 * Composite validator delegating to child rules.
 *
 * @implements \IteratorAggregate<int, ConfigurationValidationRuleInterface>
 */
final class CompositeConfigurationValidator implements ConfigurationValidatorInterface, \IteratorAggregate
{
    /**
     * @param iterable<ConfigurationValidationRuleInterface> $rules
     */
    public function __construct(private readonly iterable $rules)
    {
    }

    public function validate(P2PClientConfigurationInterface $configuration, P2PMode $mode): ConfigurationValidationResult
    {
        $result = new ConfigurationValidationResult();

        foreach ($this->rules as $rule) {
            foreach ($rule->supports() as $supportedMode) {
                if ($supportedMode === $mode) {
                    $rule->validate($configuration, $mode, $result);
                    break;
                }
            }
        }

        return $result;
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator(is_array($this->rules) ? $this->rules : iterator_to_array($this->rules, preserve_keys: false));
    }
}
