<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WithdrawalResource\Pages;
use App\Models\Withdrawal;
use App\Service\WithdrawalService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WithdrawalResource extends Resource
{
    protected static ?string $model = Withdrawal::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Выплаты';

    protected static ?string $modelLabel = 'Выплата';

    protected static ?string $pluralModelLabel = 'Выплаты';

    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Информация о заявке')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'username')
                            ->label('Пользователь')
                            ->disabled()
                            ->prefixIcon('heroicon-o-user'),
                        Forms\Components\TextInput::make('amount')
                            ->label('Сумма')
                            ->disabled()
                            ->prefix('₽'),
                        Forms\Components\TextInput::make('method')
                            ->label('Метод')
                            ->disabled(),
                        Forms\Components\TextInput::make('card_number')
                            ->label('Номер карты/телефон')
                            ->disabled(),
                        Forms\Components\TextInput::make('bank_name')
                            ->label('Банк')
                            ->disabled(),
                        Forms\Components\Select::make('status')
                            ->label('Статус')
                            ->options([
                                'pending' => 'Ожидает',
                                'processing' => 'В обработке',
                                'completed' => 'Выполнено',
                                'rejected' => 'Отклонено',
                            ])
                            ->disabled(),
                    ])->columns(2),

                Forms\Components\Section::make('Обработка')
                    ->schema([
                        Forms\Components\Textarea::make('admin_comment')
                            ->label('Комментарий')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.username')
                    ->label('Пользователь')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => 'ID: '.$record->user_id),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Сумма')
                    ->money('RUB', locale: 'ru')
                    ->sortable()
                    ->color('success')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('method')
                    ->label('Метод')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'sbp' => 'СБП',
                        'card' => 'Карта',
                        default => $state,
                    })
                    ->color(fn ($state) => match ($state) {
                        'sbp' => 'info',
                        'card' => 'primary',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('card_number')
                    ->label('Реквизиты')
                    ->searchable()
                    ->copyable()
                    ->description(fn ($record) => $record->bank_name),
                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'pending' => 'Ожидает',
                        'processing' => 'В обработке',
                        'completed' => 'Выполнено',
                        'rejected' => 'Отклонено',
                        default => $state,
                    })
                    ->color(fn ($state) => match ($state) {
                        'pending' => 'warning',
                        'processing' => 'info',
                        'completed' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('processedByAdmin.name')
                    ->label('Обработал')
                    ->placeholder('-')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создана')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('processed_at')
                    ->label('Обработана')
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('-')
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'pending' => 'Ожидает',
                        'processing' => 'В обработке',
                        'completed' => 'Выполнено',
                        'rejected' => 'Отклонено',
                    ]),
                Tables\Filters\Filter::make('pending_only')
                    ->label('Только ожидающие')
                    ->query(fn (Builder $query): Builder => $query->where('status', 'pending'))
                    ->default(),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Выплатить')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Подтвердить выплату')
                    ->modalDescription(fn ($record) => "Вы уверены, что выплатили {$record->amount} ₽ на {$record->card_number}?")
                    ->visible(fn ($record) => in_array($record->status, ['pending', 'processing']))
                    ->action(function ($record) {
                        try {
                            $service = app(WithdrawalService::class);
                            $service->approve($record, filament()->auth()->user());

                            Notification::make()
                                ->success()
                                ->title('Выплата подтверждена')
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title('Ошибка')
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),

                Tables\Actions\Action::make('reject')
                    ->label('Отклонить')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Отклонить заявку')
                    ->form([
                        Forms\Components\Textarea::make('comment')
                            ->label('Причина отклонения')
                            ->required(),
                    ])
                    ->visible(fn ($record) => in_array($record->status, ['pending', 'processing']))
                    ->action(function ($record, array $data) {
                        try {
                            $service = app(WithdrawalService::class);
                            $service->reject($record, filament()->auth()->user(), $data['comment']);

                            Notification::make()
                                ->success()
                                ->title('Заявка отклонена')
                                ->body('Средства возвращены на баланс пользователя')
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title('Ошибка')
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),

                Tables\Actions\Action::make('take')
                    ->label('Взять')
                    ->icon('heroicon-o-hand-raised')
                    ->color('info')
                    ->visible(fn ($record) => $record->status === 'pending')
                    ->action(function ($record) {
                        try {
                            $service = app(WithdrawalService::class);
                            $service->takeInProcessing($record, filament()->auth()->user());

                            Notification::make()
                                ->success()
                                ->title('Заявка взята в обработку')
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title('Ошибка')
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),

                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->poll('10s');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWithdrawals::route('/'),
            'view' => Pages\ViewWithdrawal::route('/{record}'),
        ];
    }
}
