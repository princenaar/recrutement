<?php

namespace App\Support;

class InvitationBatchResult
{
    public function __construct(
        public readonly int $sent = 0,
        public readonly int $skippedNoEmail = 0,
        public readonly int $skippedActiveInvitation = 0,
        public readonly int $failed = 0,
    ) {}

    public function total(): int
    {
        return $this->sent + $this->skippedNoEmail + $this->skippedActiveInvitation + $this->failed;
    }

    public function toArray(): array
    {
        return [
            'sent' => $this->sent,
            'skipped_no_email' => $this->skippedNoEmail,
            'skipped_active_invitation' => $this->skippedActiveInvitation,
            'failed' => $this->failed,
            'total' => $this->total(),
        ];
    }
}
