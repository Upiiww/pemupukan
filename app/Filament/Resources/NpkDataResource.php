<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NpkDataResource\Pages;
use App\Filament\Resources\NpkDataResource\RelationManagers;
use App\Models\NpkData;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class NpkDataResource extends Resource
{
    protected static ?string $model = NpkData::class;

    protected static ?string $navigationIcon = 'heroicon-o-eye-dropper';
    protected static ?string $navigationGroup = 'Sensor';
    protected static ?string $navigationLabel = 'sensor NPK';
    protected static ?string $slug = 'npk';
    protected static ?string $label = 'Data sensor NPK';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
            Forms\Components\TextInput::make('device_id')->required()->numeric(),
            Forms\Components\TextInput::make('nitrogen')->required()->numeric(),
            Forms\Components\TextInput::make('phosphorus')->required()->numeric(),
            Forms\Components\TextInput::make('potassium')->required()->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable()
                ->label('No'), 
                TextColumn::make('nitrogen'),
                TextColumn::make('phosphorus'),
                TextColumn::make('potassium'),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
             //   Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListNpkData::route('/'),
            'create' => Pages\CreateNpkData::route('/create'),
            'edit' => Pages\EditNpkData::route('/{record}/edit'),
        ];
    }
}
