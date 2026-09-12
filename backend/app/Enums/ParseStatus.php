<?php

namespace App\Enums;

enum ParseStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Success = 'success';
    case FailedStructureChanged = 'failed_structure_changed';
    case FailedBlocked = 'failed_blocked';
    case FailedUnavailable = 'failed_unavailable';
}
