<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PromocodesResource\Pages;
use App\Filament\Resources\PromocodesResource\RelationManagers;
use App\Models\Promocodes;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PromocodesResource extends Resource
{
    protected static ?string $model = Promocodes::class;

    protected static ?string $navigationIcon = 'heroicon-o-gift';

    protected static ?string $navigationLabel = 'Промокоды';

    protected static ?string $modelLabel = 'Промокод';

    protected static ?string $pluralModelLabel = 'Промокоды';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Название промокода')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->prefixIcon('heroicon-o-gift')
                            ->helperText('Уникальный код промокода (например: WELCOME2024)')
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('amount')
                            ->label('Сумма награды')
                            ->numeric()
                            ->required()
                            ->prefix('₽')
                            ->prefixIcon('heroicon-o-banknotes')
                            ->default(0)
                            ->columnSpan(2),
                        Forms\Components\TextInput::make('activate_limit')
                            ->label('Лимит активаций')
                            ->numeric()
                            ->required()
                            ->default(1)
                            ->prefixIcon('heroicon-o-users')
                            ->helperText('Максимальное количество использований')
                            ->columnSpan(2),
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
                    ->label('Промокод')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-o-gift')
                    ->copyable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Награда')
                    ->money('RUB', locale: 'ru')
                    ->sortable()
                    ->color('success')
                    ->icon('heroicon-o-banknotes')
                    ->alignEnd(),
                Tables\Columns\TextColumn::make('activate')
                    ->label('Активаций')
                    ->sortable()
                    ->badge()
                    ->color(fn ($record) => $record->activate >= $record->activate_limit ? 'danger' : 'success')
                    ->icon('heroicon-o-check-circle'),
                Tables\Columns\TextColumn::make('activate_limit')
                    ->label('Лимит')
                    ->sortable()
                    ->badge()
                    ->color('gray')
                    ->icon('heroicon-o-users'),
                Tables\Columns\TextColumn::make('remaining')
                    ->label('Осталось')
                    ->getStateUsing(fn ($record) => max(0, $record->activate_limit - $record->activate))
                    ->badge()
                    ->color(fn ($record) => max(0, $record->activate_limit - $record->activate) > 0 ? 'success' : 'danger')
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderByRaw("(activate_limit - activate) {$direction}");
                    }),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Активен')
                    ->getStateUsing(fn ($record) => $record->activate < $record->activate_limit)
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Обновлен')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('is_active')
                    ->label('Активные')
                    ->query(fn (Builder $query): Builder => $query->whereColumn('activate', '<', 'activate_limit')),
                Tables\Filters\Filter::make('is_expired')
                    ->label('Использованы')
                    ->query(fn (Builder $query): Builder => $query->whereColumn('activate', '>=', 'activate_limit')),
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
            'index' => Pages\ListPromocodes::route('/'),
            'create' => Pages\CreatePromocodes::route('/create'),
            'view' => Pages\ViewPromocodes::route('/{record}'),
            'edit' => Pages\EditPromocodes::route('/{record}/edit'),
        ];
    }
}
