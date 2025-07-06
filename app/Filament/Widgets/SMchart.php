<?php

namespace App\Filament\Widgets;

use App\Models\SoilData;
use Filament\Widgets\ChartWidget;

class SMChart extends ChartWidget
{
    protected static ?string $heading = 'Diagram Kelembapan Tanah';

    protected function getData(): array
    {
        // Ambil 10 data terakhir (urutkan dari yang terbaru, lalu dibalik agar urut waktu)
        $npkData = SoilData::orderBy('created_at', 'desc')->take(10)->get()->reverse();

        // Label sumbu X (timestamp)
        $labels = $npkData->pluck('created_at')->map(function ($date) {
            return $date->format('d M H:i');
        })->toArray();

        // Ambil nilai kelembapan
        $moistureValues = $npkData->pluck('soil_moisture')->toArray();

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Kelembapan Tanah (%)',
                    'data' => $moistureValues,
                    'borderColor' => '#4CAF50',
                    'backgroundColor' => 'rgba(76,175,80,0.2)',
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
