<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\Bank;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\Group::make([
                            Forms\Components\TextInput::make('username')
                                ->label('Username')
                                ->maxLength(255)
                                ->columnSpan(2),
                            Forms\Components\Select::make('bank_id')
                                ->options(Bank::all()->pluck('name', 'id'))
                                ->label('Банк')
                                ->native(false)
                                ->required()
                                ->searchable()
                                ->preload()
                                ->prefixIcon('heroicon-o-banknotes')
                                ->columnSpan(2),
                        ])->columns(4),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Telegram данные')
                    ->schema([
                        Forms\Components\Group::make([
                            Forms\Components\TextInput::make('tg_id')
                                ->label('Telegram ID')
                                ->numeric()
                                ->prefixIcon('heroicon-o-chat-bubble-left-right')
                                ->columnSpan(2),
                            Forms\Components\TextInput::make('username')
                                ->label('Telegram Username')
                                ->maxLength(255)
                                ->prefix('@')
                                ->columnSpan(2),
                        ])->columns(4),
                        Forms\Components\TextInput::make('avatar')
                            ->label('Аватар (URL)')
                            ->url()
                            ->maxLength(255)
                            ->prefixIcon('heroicon-o-photo')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Реферальная система')
                    ->schema([
                        Forms\Components\Group::make([
                            Forms\Components\TextInput::make('ref_code')
                                ->label('Реферальный код')
                                ->maxLength(255)
                                ->unique(ignoreRecord: true)
                                ->prefixIcon('heroicon-o-gift')
                                ->columnSpan(2),
                                Forms\Components\Select::make('referrer_id')
                                ->relationship('referrer', 'username', fn ($query) => $query->whereNotNull('username'))
                                ->searchable()
                                ->preload()
                                ->label('Реферер')
                                ->prefixIcon('heroicon-o-user-group')
                                ->getOptionLabelFromRecordUsing(fn ($record) => $record->name ?? $record->username ?? "ID: {$record->id}")
                                ->columnSpan(2),
                        ])->columns(4),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Балансы')
                    ->schema([
                        Forms\Components\Group::make([
                            Forms\Components\TextInput::make('balance')
                                ->label('Баланс')
                                ->numeric()
                                ->default(0)
                                ->required()
                                ->prefix('₽')
                                ->prefixIcon('heroicon-o-banknotes')
                                ->columnSpan(2),
                            Forms\Components\TextInput::make('ref_balance')
                                ->label('Реферальный баланс')
                                ->numeric()
                                ->default(0)
                                ->required()
                                ->prefix('₽')
                                ->prefixIcon('heroicon-o-gift')
                                ->columnSpan(2),
                        ])->columns(4),
                        Forms\Components\TextInput::make('bonus_time')
                            ->label('Время последнего бонуса')
                            ->numeric()
                            ->helperText('Unix timestamp')
                            ->prefixIcon('heroicon-o-clock')
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
                Tables\Columns\ImageColumn::make('avatar')
                    ->label('')
                    ->circular()
                    ->size(50)
                    ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->name ?? $record->username ?? 'User') . '&background=random'),
                Tables\Columns\TextColumn::make('name')
                    ->label('Имя')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn ($record) => $record->username ? '@' . $record->username : null),
                Tables\Columns\TextColumn::make('tg_id')
                    ->label('Telegram ID')
                    ->sortable()
                    ->searchable()
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-envelope')
                    ->copyable()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('email_verified_at')
                    ->label('Email')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('ref_code')
                    ->label('Реферальный код')
                    ->searchable()
                    ->copyable()
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-o-gift')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('referrer.name')
                    ->label('Реферер')
                    ->sortable()
                    ->searchable()
                    ->icon('heroicon-o-user-group')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('balance')
                    ->label('Баланс')
                    ->money('RUB', locale: 'ru')
                    ->sortable()
                    ->color(fn ($record) => $record->balance > 0 ? 'success' : 'gray')
                    ->icon('heroicon-o-banknotes')
                    ->alignEnd(),
                Tables\Columns\TextColumn::make('ref_balance')
                    ->label('Реф. баланс')
                    ->money('RUB', locale: 'ru')
                    ->sortable()
                    ->color(fn ($record) => $record->ref_balance > 0 ? 'warning' : 'gray')
                    ->icon('heroicon-o-gift')
                    ->alignEnd()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('referrals_count')
                    ->counts('referrals')
                    ->label('Рефералов')
                    ->sortable()
                    ->badge()
                    ->color('success')
                    ->icon('heroicon-o-users')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('payments_count')
                    ->counts('payments')
                    ->label('Платежей')
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-o-credit-card')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('bonus_time')
                    ->label('Последний бонус')
                    ->formatStateUsing(fn ($state) => $state ? date('d.m.Y H:i', $state) : '-')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('has_balance')
                    ->label('С балансом')
                    ->query(fn (Builder $query): Builder => $query->where('balance', '>', 0)),
                Tables\Filters\Filter::make('has_ref_balance')
                    ->label('С реферальным балансом')
                    ->query(fn (Builder $query): Builder => $query->where('ref_balance', '>', 0)),
                Tables\Filters\Filter::make('has_referrals')
                    ->label('С рефералами')
                    ->query(fn (Builder $query): Builder => $query->has('referrals')),
                Tables\Filters\Filter::make('email_verified')
                    ->label('Email подтвержден')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('email_verified_at')),
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
            ->striped()
            ->poll('30s');
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
