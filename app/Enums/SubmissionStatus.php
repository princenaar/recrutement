<?php

namespace App\Enums;

enum SubmissionStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case Shortlisted = 'shortlisted';
    case Rejected = 'rejected';
}
