<?php

namespace App\Filament\Resources\SmsLogs\Pages;

use App\Filament\Resources\SmsLogs\SmsLogResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSmsLog extends CreateRecord
{
    protected static string $resource = SmsLogResource::class;
}
