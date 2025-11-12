<?php

declare(strict_types=1);

namespace OpenFinancy\Component\P2PClient\Validation;

enum P2PMode: string
{
    case PEER = 'peer';
    case PROVIDER = 'provider';
}
