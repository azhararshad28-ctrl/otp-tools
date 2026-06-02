<?php

namespace App\Filament\Resources\OtpLogs\Pages;

use App\Filament\Resources\OtpLogs\OtpLogResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditOtpLog extends EditRecord
{
    protected static string $resource = OtpLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
