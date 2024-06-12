<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    protected bool $persistsFiltersInSession = true;

    public function mount()
    {
        if (!isset($this->filters['period']))
            $this->filters['period'] = 'this_week';
    }

    public function filtersForm(Form $form): Form
    {
        return $form
            ->columns(5)
            ->schema([
                Select::make('period')
                    ->columnStart(5)
                    ->hiddenLabel()
                    ->visible(auth()->user()->hasRole('Admin'))
                    ->options([
                        'today' => 'Aujourd\'hui',
                        'yesterday' => 'Hier',
                        'last_7_days' => '7 derniers jours',
                        'this_week' => 'Cette semaine',
                        'last_week' => 'La semaine dernière',
                        'last_30_days' => '30 derniers jours',
                        'this_month' => 'Ce mois-ci',
                        'last_month' => 'Le mois dernier',
                        'this_year' => 'Cette année',
                        'last_year' => 'L\'année dernière',
                    ])
                    ->selectablePlaceholder(false),
            ]);
    }
}
