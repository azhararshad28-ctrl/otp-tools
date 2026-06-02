<?php

namespace App\Filament\Resources\SmsLogs\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class SmsLogForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('phone_number_id')
                    ->tel()
                    ->required()
                    ->numeric(),
                TextInput::make('sender')
                    ->required(),
                Textarea::make('message')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('otp'),
                DateTimePicker::make('received_time')
                    ->required(),
            ]);
    }
}
