<?php

namespace App\Filament\Widgets;

use App\Models\NpkData;
use Filament\Widgets\ChartWidget;

class Pchart extends ChartWidget
{
    protected static ?string $heading = 'Diagram Phosphorus';

    protected function getData(): array
    {
        // Ambil 10 data terakhir (urutkan ascending)
        $npkData = NpkData::orderBy('created_at', 'desc')->take(10)->get()->reverse();

        // Labels X Axis (created_at)
        $labels = $npkData->pluck('created_at')->map(function ($date) {
            return $date->format('d M H:i');
        })->toArray();

        // Dataset Y Axis (phosphorus)
        $phosphorusValues = $npkData->pluck('phosphorus')->toArray();

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Phosphorus (mg/kg)',
                    'data' => $phosphorusValues,
                    'borderColor' => '#FF6384',
                    'backgroundColor' => 'rgba(255,99,132,0.2)',
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
