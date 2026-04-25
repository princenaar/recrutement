<?php

namespace App\Exceptions;

use App\Models\InvitationToken;
use RuntimeException;

class ActiveInvitationExistsException extends RuntimeException
{
    public function __construct(public readonly InvitationToken $existingToken)
    {
        parent::__construct(
            "An active invitation token already exists for agent #{$existingToken->agent_id} and campaign #{$existingToken->campaign_id}."
        );
    }
}
