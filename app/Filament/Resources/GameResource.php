<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GameResource\Pages;
use App\Filament\Resources\GameResource\RelationManagers;
use App\Models\Games;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class GameResource extends Resource
{
    protected static ?string $model = Games::class;

    protected static ?string $navigationIcon = 'heroicon-o-play';

    protected static ?string $navigationLabel = 'Игры';

    protected static ?string $modelLabel = 'Игра';

    protected static ?string $pluralModelLabel = 'Игры';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\Group::make([
                            Forms\Components\TextInput::make('id_game')
                                ->label('ID игры')
                                ->required()
                                ->maxLength(255)
                                ->unique(ignoreRecord: true)
                                ->prefixIcon('heroicon-o-identification')
                                ->helperText('Уникальный идентификатор игры')
                                ->columnSpan(2),
                            Forms\Components\TextInput::make('url_slug')
                                ->label('URL Slug')
                                ->required()
                                ->maxLength(255)
                                ->prefixIcon('heroicon-o-link')
                                ->helperText('URL-дружественное имя')
                                ->columnSpan(2),
                        ])->columns(4),
                        Forms\Components\Textarea::make('description')
                            ->label('Описание')
                            ->maxLength(65535)
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\FileUpload::make('image')
                            ->label('Изображение')
                            ->image()
                            ->directory('/assets/img')
                            ->disk('public')

                            ->columnSpanFull(),
                        Forms\Components\Select::make('bank_id')
                            ->relationship('bank', 'name')
                            ->label('Банк')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->prefixIcon('heroicon-o-banknotes')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label('')
                    ->circular()
                    ->size(50)
                    ->defaultImageUrl('https://via.placeholder.com/50'),
                Tables\Columns\TextColumn::make('id_game')
                    ->label('ID игры')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable()
                    ->icon('heroicon-o-identification'),
                Tables\Columns\TextColumn::make('description')
                    ->label('Описание')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(fn($record) => $record->description)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('url_slug')
                    ->label('URL Slug')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->icon('heroicon-o-link')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('bank.name')
                    ->label('Банк')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-o-banknotes'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создана')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Обновлена')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('bank_id')
                    ->label('Банк')
                    ->relationship('bank', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped();
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
            'index' => Pages\ListGames::route('/'),
            'create' => Pages\CreateGame::route('/create'),
            'view' => Pages\ViewGame::route('/{record}'),
            'edit' => Pages\EditGame::route('/{record}/edit'),
        ];
    }
}
