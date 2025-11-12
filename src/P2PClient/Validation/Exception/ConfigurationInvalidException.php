<?php

declare(strict_types=1);

namespace OpenFinancy\Component\P2PClient\Validation\Exception;

use OpenFinancy\Component\P2PClient\Validation\ConfigurationValidationResult;
use OpenFinancy\Component\P2PClient\Validation\P2PMode;

final class ConfigurationInvalidException extends \RuntimeException
{
    public function __construct(
        private readonly P2PMode $mode,
        private readonly ConfigurationValidationResult $result
    ) {
        parent::__construct(sprintf(
            'Configuration invalid for %s operations: %s',
            $mode->value,
            implode('; ', $result->getErrors())
        ));
    }

    public function getMode(): P2PMode
    {
        return $this->mode;
    }

    public function getResult(): ConfigurationValidationResult
    {
        return $this->result;
    }
}
