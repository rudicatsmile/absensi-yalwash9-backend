<?php

namespace App\Filament\Resources\LeaveBalances;

use App\Filament\Resources\LeaveBalances\Pages\EditLeaveBalance;
use App\Filament\Resources\LeaveBalances\Pages\ListLeaveBalances;
use App\Filament\Resources\LeaveBalances\Schemas\LeaveBalanceForm;
use App\Filament\Resources\LeaveBalances\Tables\LeaveBalancesTable;
use App\Models\LeaveBalance;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class LeaveBalanceResource extends Resource
{
    protected static ?string $model = LeaveBalance::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static UnitEnum|string|null $navigationGroup = 'Data';

    protected static ?string $navigationLabel = 'Saldo Cuti';

    protected static ?string $pluralLabel = 'Saldo Cuti';

    protected static ?int $navigationSort = 42;

    public static function form(Schema $schema): Schema
    {
        return LeaveBalanceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LeaveBalancesTable::configure($table);
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
            'index' => ListLeaveBalances::route('/'),
            'edit' => EditLeaveBalance::route('/{record}/edit'),
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
}
