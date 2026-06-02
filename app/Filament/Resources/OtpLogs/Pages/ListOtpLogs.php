<?php

namespace App\Filament\Resources\OtpLogs\Pages;

use App\Filament\Resources\OtpLogs\OtpLogResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOtpLogs extends ListRecords
{
    protected static string $resource = OtpLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
