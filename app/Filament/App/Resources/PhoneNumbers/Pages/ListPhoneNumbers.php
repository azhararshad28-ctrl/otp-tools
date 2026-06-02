<?php

namespace App\Filament\App\Resources\PhoneNumbers\Pages;

use App\Filament\App\Resources\PhoneNumbers\PhoneNumberResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPhoneNumbers extends ListRecords
{
    protected static string $resource = PhoneNumberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
