<?php

namespace App\Filament\Widgets;

use App\Models\NpkData;
use Filament\Widgets\ChartWidget;

class Nchart extends ChartWidget
{
    protected static ?string $heading = 'Diagram Nitrogen';

    protected function getData(): array
    {
        // Ambil 10 data terakhir (urutkan ascending)
        $npkData = NpkData::orderBy('created_at', 'desc')->take(10)->get()->reverse();

        // Labels X Axis (created_at)
        $labels = $npkData->pluck('created_at')->map(function ($date) {
            return $date->format('d M H:i');
        })->toArray();

        // Dataset Y Axis (nitrogen)
        $nitrogenValues = $npkData->pluck('nitrogen')->toArray();

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Nitrogen (mg/kg)',
                    'data' => $nitrogenValues,
                    'borderColor' => '#36A2EB',
                    'backgroundColor' => 'rgba(54,162,235,0.2)',
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
