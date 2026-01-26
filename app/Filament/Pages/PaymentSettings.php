<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class PaymentSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string $view = 'filament.pages.payment-settings';

    protected static ?string $navigationLabel = 'Настройки платежей';

    protected static ?string $title = 'Настройки платежей';

    protected static ?string $navigationGroup = 'Настройки';

    protected static ?int $navigationSort = 100;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'stars_rate' => Setting::getValue('stars_rate', 2.5),
            'stars_min_amount' => Setting::getValue('stars_min_amount', 50),
            'stars_max_amount' => Setting::getValue('stars_max_amount', 500000),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Telegram Stars ⭐')
                    ->description('Настройки оплаты через Telegram Stars')
                    ->schema([
                        TextInput::make('stars_rate')
                            ->label('Курс Stars к RUB')
                            ->helperText('Сколько рублей стоит 1 Telegram Star. Например: 2.5 означает 1⭐ = 2.5₽')
                            ->numeric()
                            ->minValue(0.01)
                            ->maxValue(1000)
                            ->step(0.01)
                            ->required()
                            ->suffix('₽ за 1⭐'),

                        TextInput::make('stars_min_amount')
                            ->label('Минимальная сумма пополнения')
                            ->helperText('Минимальная сумма в рублях для пополнения через Stars')
                            ->numeric()
                            ->minValue(1)
                            ->required()
                            ->suffix('₽'),

                        TextInput::make('stars_max_amount')
                            ->label('Максимальная сумма пополнения')
                            ->helperText('Максимальная сумма в рублях для пополнения через Stars')
                            ->numeric()
                            ->minValue(100)
                            ->required()
                            ->suffix('₽'),
                    ])
                    ->columns(3),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        Setting::setValue('stars_rate', $data['stars_rate'], 'float');
        Setting::setValue('stars_min_amount', $data['stars_min_amount'], 'integer');
        Setting::setValue('stars_max_amount', $data['stars_max_amount'], 'integer');

        Notification::make()
            ->title('Настройки сохранены')
            ->success()
            ->send();
    }
}
