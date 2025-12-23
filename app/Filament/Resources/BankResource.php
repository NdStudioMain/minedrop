<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BankResource\Pages;
use App\Filament\Resources\BankResource\RelationManagers;
use App\Models\Bank;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BankResource extends Resource
{
    protected static ?string $model = Bank::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Банки';

    protected static ?string $modelLabel = 'Банк';

    protected static ?string $pluralModelLabel = 'Банки';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\Group::make([
                            Forms\Components\TextInput::make('name')
                                ->label('Название банка')
                                ->required()
                                ->maxLength(255)
                                ->prefixIcon('heroicon-o-building-library')
                                ->columnSpan(2),
                            Forms\Components\Select::make('currency')
                                ->label('Валюта')
                                ->options([
                                    'USD' => 'USD',
                                    'EUR' => 'EUR',
                                    'RUB' => 'RUB',
                                ])
                                ->default('USD')
                                ->required()
                                ->prefixIcon('heroicon-o-currency-dollar')
                                ->columnSpan(2),
                        ])->columns(4),
                        Forms\Components\TextInput::make('default_balance')
                            ->label('Начальный баланс')
                            ->numeric()
                            ->default(0)
                            ->required()
                            ->prefixIcon('heroicon-o-banknotes')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Статистика')
                    ->schema([
                        Forms\Components\Group::make([
                            Forms\Components\TextInput::make('totalBets')
                                ->label('Всего ставок')
                                ->numeric()
                                ->default(0)
                                ->prefixIcon('heroicon-o-arrow-trending-up')
                                ->columnSpan(2),
                            Forms\Components\TextInput::make('totalWins')
                                ->label('Всего выигрышей')
                                ->numeric()
                                ->default(0)
                                ->prefixIcon('heroicon-o-trophy')
                                ->columnSpan(2),
                        ])->columns(4),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Настройки игры')
                    ->schema([
                        Forms\Components\Group::make([
                            Forms\Components\TextInput::make('rtp')
                                ->label('RTP (%)')
                                ->numeric()
                                ->default(0)
                                ->suffix('%')
                                ->prefixIcon('heroicon-o-chart-bar')
                                ->helperText('Return to Player - процент возврата игроку')
                                ->columnSpan(2),
                            Forms\Components\TextInput::make('houseEdge')
                                ->label('House Edge')
                                ->numeric()
                                ->default(0.05)
                                ->step(0.01)
                                ->prefixIcon('heroicon-o-scale')
                                ->helperText('Преимущество казино')
                                ->columnSpan(2),
                        ])->columns(4),
                        Forms\Components\TextInput::make('maxPayoutPercent')
                            ->label('Максимальный процент выплаты')
                            ->numeric()
                            ->default(0.05)
                            ->step(0.01)
                            ->suffix('%')
                            ->prefixIcon('heroicon-o-shield-check')
                            ->helperText('Максимальный процент от банка для выплаты')
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
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon('heroicon-o-building-library'),
                Tables\Columns\TextColumn::make('currency')
                    ->label('Валюта')
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-o-currency-dollar')
                    ->sortable(),
                Tables\Columns\TextColumn::make('default_balance')
                    ->label('Баланс')
                    ->money('RUB', locale: 'ru')
                    ->sortable()
                    ->color('success')
                    ->icon('heroicon-o-banknotes')
                    ->alignEnd(),
                Tables\Columns\TextColumn::make('totalBets')
                    ->label('Ставки')
                    ->money('RUB', locale: 'ru')
                    ->sortable()
                    ->icon('heroicon-o-arrow-trending-up')
                    ->alignEnd()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('totalWins')
                    ->label('Выигрыши')
                    ->money('RUB', locale: 'ru')
                    ->sortable()
                    ->color('success')
                    ->icon('heroicon-o-trophy')
                    ->alignEnd()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('rtp')
                    ->label('RTP')
                    ->suffix('%')
                    ->sortable()
                    ->badge()
                    ->color(fn ($record) => $record->rtp >= 95 ? 'success' : ($record->rtp >= 90 ? 'warning' : 'danger'))
                    ->icon('heroicon-o-chart-bar')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('houseEdge')
                    ->label('House Edge')
                    ->suffix('%')
                    ->sortable()
                    ->badge()
                    ->color('gray')
                    ->icon('heroicon-o-scale')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('games_count')
                    ->counts('games')
                    ->label('Игр')
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-o-play')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('currency')
                    ->label('Валюта')
                    ->options([
                        'USD' => 'USD',
                        'EUR' => 'EUR',
                        'RUB' => 'RUB',
                    ]),
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
            'index' => Pages\ListBanks::route('/'),
            'create' => Pages\CreateBank::route('/create'),
            'view' => Pages\ViewBank::route('/{record}'),
            'edit' => Pages\EditBank::route('/{record}/edit'),
        ];
    }
}
