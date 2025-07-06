<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SoilDataResource\Pages;
use App\Filament\Resources\SoilDataResource\RelationManagers;
use App\Models\SoilData;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SoilDataResource extends Resource
{
    protected static ?string $model = SoilData::class;

    protected static ?string $navigationIcon = 'heroicon-o-sun';
    protected static ?string $navigationGroup = 'Sensor';
    protected static ?string $navigationLabel = 'sensor Tanah';
    protected static ?string $slug = 'tanah';
    protected static ?string $label = 'Data Sensor Tanah';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
            Forms\Components\TextInput::make('soil_moisture')->required()->numeric(),
            Forms\Components\TextInput::make('soil_temperature')->required()->numeric(),
            Forms\Components\TextInput::make('soil_ph')->required()->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable()
                ->label('No'), 
                TextColumn::make('soil_moisture')
                ->label('Kelembapan Tanah'),
                TextColumn::make('soil_temperature')
                ->label('Suhu Tanah'),
                TextColumn::make('soil_ph')
                ->label('Ph Tanah')
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSoilData::route('/'),
            'create' => Pages\CreateSoilData::route('/create'),
            'edit' => Pages\EditSoilData::route('/{record}/edit'),
        ];
    }
}
