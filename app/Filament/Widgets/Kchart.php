<?php

namespace App\Filament\Widgets;

use App\Models\NpkData;
use Filament\Widgets\ChartWidget;

class Kchart extends ChartWidget
{
    protected static ?string $heading = 'Diagram potassium';

    protected function getData(): array
    {
        // Ambil 10 data terakhir (urutkan descending lalu reverse agar ascending)
        $npkData = NpkData::orderBy('created_at', 'desc')->take(10)->get()->reverse();

        // Label sumbu X (waktu)
        $labels = $npkData->pluck('created_at')->map(function ($date) {
            return $date->format('d M H:i');
        })->toArray();

        // Data kalium
        $kaliumValues = $npkData->pluck('potassium')->toArray();

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Kalium (mg/kg)',
                    'data' => $kaliumValues,
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
