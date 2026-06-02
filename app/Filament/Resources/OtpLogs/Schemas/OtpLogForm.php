<?php

namespace App\Filament\Resources\OtpLogs\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class OtpLogForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('sms_log_id')
                    ->required()
                    ->numeric(),
                TextInput::make('code')
                    ->required(),
            ]);
    }
}
