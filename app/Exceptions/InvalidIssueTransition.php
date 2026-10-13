<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Enums\IssueStatus;
use DomainException;

final class InvalidIssueTransition extends DomainException
{
    public function __construct(IssueStatus $from, IssueStatus $to)
    {
        parent::__construct(sprintf(
            'Issue status cannot transition from "%s" to "%s".',
            $from->value,
            $to->value,
        ));
    }
}
