<?php

namespace App\Filament\Resources\PhoneNumbers\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PhoneNumberForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('country_id')
                    ->required()
                    ->numeric(),
                TextInput::make('number')
                    ->required(),
                TextInput::make('provider')
                    ->required()
                    ->default('RapidAPI'),
                TextInput::make('status')
                    ->required()
                    ->default('active'),
                DateTimePicker::make('last_checked'),
            ]);
    }
}
