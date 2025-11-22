<?php

namespace App\Filament\Resources\Permits;

use App\Filament\Resources\Permits\Pages\CreatePermit;
use App\Filament\Resources\Permits\Pages\EditPermit;
use App\Filament\Resources\Permits\Pages\ListPermits;
use App\Filament\Resources\Permits\Pages\ViewPermit;
use App\Filament\Resources\Permits\Schemas\PermitForm;
use App\Filament\Resources\Permits\Tables\PermitsTable;
use App\Models\Permit;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class PermitResource extends Resource
{
    protected static ?string $model = Permit::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static UnitEnum|string|null $navigationGroup = 'Data';

    protected static ?string $navigationLabel = 'Izin Kerja';

    protected static ?string $pluralLabel = 'Izin Kerja';

    protected static ?int $navigationSort = 43;

    public static function form(Schema $schema): Schema
    {
        return PermitForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PermitsTable::configure($table);
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
            'index' => ListPermits::route('/'),
            'create' => CreatePermit::route('/create'),
            'view' => ViewPermit::route('/{record}'),
            'edit' => EditPermit::route('/{record}/edit'),
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
        $query = parent::getEloquentQuery();

        if (auth()->check()) {
            $role = auth()->user()->role;
            if ($role === 'employee') {
                return $query->where('employee_id', auth()->id());
            }
            if (in_array($role, ['manager','kepala_sub_bagian'], true)) {
                return $query->whereHas('employee', function ($q) {
                    $q->where('departemen_id', auth()->user()->departemen_id);
                });
            }
        }

        return $query;
    }

    public static function canCreate(): bool
    {
        if (!auth()->check()) return false;
        return ! in_array(auth()->user()->role, ['employee','manager','kepala_sub_bagian'], true);
    }
}
