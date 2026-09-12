<?php

namespace App\Enums;

enum ParseRunStatus: string
{
    case Running = 'running';
    case Success = 'success';
    case Failed = 'failed';
}
