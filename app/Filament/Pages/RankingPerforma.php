<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class RankingPerforma extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-trophy';

    protected static string|\UnitEnum|null $navigationGroup = 'Laporan & Analitik';

    protected string $view = 'filament.pages.ranking-performa';
}
