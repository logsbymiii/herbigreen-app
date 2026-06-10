<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class CustomDashboardWidget extends Widget
{
    protected string $view = 'filament.widgets.custom-dashboard-widget';

    protected int | string | array $columnSpan = 'full';
}
