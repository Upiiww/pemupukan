<?php

namespace App\Http\Controllers;

use App\Models\SoilData;
use Illuminate\Http\Request;

class SoilDataController extends Controller
{
    public function index()
    {
    return SoilData::all();
    }
    public function store(Request $request)
{
    $validated = $request->validate([
        'device_id' => 'required|integer',
        'soil_moisture' => 'required|numeric',
        'soil_temperature' => 'required|numeric',
        'soil_ph' => 'required|numeric',
    ]);

    $data = SoilData::create($validated);

    $formattedData = $data->toArray();
    $formattedData['soil_moisture'] = number_format($formattedData['soil_moisture'], 2);
    $formattedData['soil_temperature'] = number_format($formattedData['soil_temperature'], 2);
    $formattedData['soil_ph'] = number_format($formattedData['soil_ph'], 2);

    return response()->json([
        'message' => 'Soil data saved successfully',
        'data' => $formattedData,
    ], 201);
}
}
 

