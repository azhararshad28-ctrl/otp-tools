<?php

namespace App\Filament\Resources\ApiLogs\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class ApiLogForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('endpoint')
                    ->required(),
                TextInput::make('method')
                    ->required(),
                Textarea::make('request_payload')
                    ->columnSpanFull(),
                Textarea::make('response_payload')
                    ->columnSpanFull(),
                TextInput::make('status_code')
                    ->required()
                    ->numeric(),
                TextInput::make('execution_time')
                    ->numeric(),
            ]);
    }
}
