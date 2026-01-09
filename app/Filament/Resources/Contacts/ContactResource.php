<?php

namespace App\Filament\Resources\Contacts;

use App\Filament\Resources\Contacts\Pages;
use App\Filament\Resources\Contacts\Schemas\ContactForm;
use App\Filament\Resources\Contacts\Tables\ContactsTable;
use App\Models\Contact;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class ContactResource extends Resource
{
    protected static ?string $model = Contact::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-identification';

    protected static UnitEnum|string|null $navigationGroup = 'Settings';

    public static function form(Schema $schema): Schema
    {
        // Menggunakan pola yang benar dengan mendelegasikan ke kelas ContactForm
        return ContactForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        // Menggunakan pola yang benar dengan mendelegasikan ke kelas ContactsTable
        return ContactsTable::configure($table);
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
            'index' => Pages\ListContacts::route('/'),
            'create' => Pages\CreateContact::route('/create'),
            'edit' => Pages\EditContact::route('/{record}/edit'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && !in_array(auth()->user()->role, ['employee', 'manager', 'kepala_sub_bagian'], true);

    }

    public static function canViewAny(): bool
    {
        return auth()->check() && !in_array(auth()->user()->role, ['employee', 'manager', 'kepala_sub_bagian'], true);

    }
}
