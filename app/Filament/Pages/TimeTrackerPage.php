<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class TimeTrackerPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static string $view = 'filament.pages.time-tracker-page';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'time-tracker';

    protected ?string $maxContentWidth = 'full';

    public function getTitle(): string|Htmlable
    {
        return __('time-tracker.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('time-tracker.title');
    }


}
