<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Users\Tables\UsersTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static UnitEnum|string|null $navigationGroup = 'Data';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'Pegawai';

    protected static ?string $modelLabel = 'Pegawai';

    protected static ?string $pluralModelLabel = 'Pegawai';

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
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
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check();
    }

    public static function canViewAny(): bool
    {
        return auth()->check();
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        try {
            $query = parent::getEloquentQuery();

            if (auth()->check()) {
                $role = auth()->user()->role;
                if ($role === 'employee') {
                    return $query->where('id', auth()->id());
                }
                if (in_array($role, ['manager','kepala_sub_bagian'], true)) {
                    $dept = auth()->user()->departemen_id;
                    if (! $dept) {
                        \Log::warning('audit:user.query.error', ['actor' => auth()->id(), 'reason' => 'departemen_id null']);
                        return $query->whereRaw('1 = 0');
                    }
                    return $query->where('departemen_id', $dept);
                }
            }

            return $query;
        } catch (\Throwable $e) {
            \Log::error('audit:user.query.exception', ['message' => $e->getMessage()]);
            return parent::getEloquentQuery()->whereRaw('1 = 0');
        }
    }
}
