<?php

namespace App\Filament\Resources\PhoneNumbers;

use App\Filament\Resources\PhoneNumbers\Pages\CreatePhoneNumber;
use App\Filament\Resources\PhoneNumbers\Pages\EditPhoneNumber;
use App\Filament\Resources\PhoneNumbers\Pages\ListPhoneNumbers;
use App\Filament\Resources\PhoneNumbers\Schemas\PhoneNumberForm;
use App\Filament\Resources\PhoneNumbers\Tables\PhoneNumbersTable;
use App\Models\PhoneNumber;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PhoneNumberResource extends Resource
{
    protected static ?string $model = PhoneNumber::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return PhoneNumberForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PhoneNumbersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPhoneNumbers::route('/'),
            'create' => CreatePhoneNumber::route('/create'),
            'edit' => EditPhoneNumber::route('/{record}/edit'),
        ];
    }
}
