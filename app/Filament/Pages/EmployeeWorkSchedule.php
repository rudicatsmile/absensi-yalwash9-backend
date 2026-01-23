<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Users\UserResource;
use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

class EmployeeWorkSchedule extends Page
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $navigationLabel = 'Pengaturan Izin Masuk';

    protected static bool $shouldRegisterNavigation = false;

    protected static UnitEnum|string|null $navigationGroup = 'Master Data';

    protected static ?string $slug = 'employee-work-schedule';

    protected string $view = 'filament.pages.blank';

    public function mount(): void
    {
        $this->redirect(UserResource::getUrl('index'));
    }
}
