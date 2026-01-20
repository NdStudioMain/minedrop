<script setup>
import axios from 'axios';
import gsap from 'gsap';
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import VSelect from 'vue-select';

// Данные загружаемые с бэкенда
const methods = ref([]);
const currencies = ref([]);
const isDataLoading = ref(true);

const selectedMethod = ref(null);
const selectedCurrency = ref(null);
const amount = ref('');
const isLoading = ref(false);
const errorMessage = ref('');
const paymentUrl = ref(null); // Ссылка на оплату после создания

// Загрузка методов и курсов с бэкенда
const loadPaymentData = async () => {
    isDataLoading.value = true;
    try {
        const response = await axios.get('/api/crypto-pay/methods');
        if (response.data.success) {
            // Методы оплаты
            methods.value = response.data.data.methods.map((m) => ({
                code: m.code,
                label: m.name,
                icon: m.icon || '/assets/img/cryptobot.png',
            }));

            // Криптовалюты с курсами
            currencies.value = response.data.data.currencies.map((c) => ({
                code: c.code,
                label: c.label,
                rate_to_rub: c.rate_to_rub,
            }));

            // Устанавливаем выбранные значения по умолчанию
            if (methods.value.length > 0) {
                selectedMethod.value = methods.value[0];
            }
            if (currencies.value.length > 0) {
                selectedCurrency.value = currencies.value[0];
            }
        }
    } catch (error) {
        console.error('Ошибка загрузки методов оплаты:', error);
        errorMessage.value = 'Не удалось загрузить методы оплаты';
    } finally {
        isDataLoading.value = false;
    }
};

// Расчёт суммы в крипте (актуальный курс с бэкенда)
const cryptoAmount = computed(() => {
    const amountNum = parseFloat(amount.value) || 0;
    if (amountNum <= 0 || !selectedCurrency.value?.rate_to_rub) return '0';

    // amountRub / rate_to_rub = crypto
    const crypto = amountNum / selectedCurrency.value.rate_to_rub;
    return crypto.toFixed(8);
});

// Форматирование курса для отображения
const formattedRate = computed(() => {
    if (!selectedCurrency.value?.rate_to_rub) return '';
    return selectedCurrency.value.rate_to_rub.toLocaleString('ru-RU', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
});

// Валидация
const isValid = computed(() => {
    const amountNum = parseFloat(amount.value) || 0;
    return (
        amountNum >= 100 &&
        amountNum <= 1000000 &&
        selectedMethod.value &&
        selectedCurrency.value
    );
});

// Отправка формы
const submitDeposit = async () => {
    if (!isValid.value || isLoading.value) return;

    isLoading.value = true;
    errorMessage.value = '';
    paymentUrl.value = null;

    try {
        const response = await axios.post('/api/crypto-pay/invoice', {
            currency: selectedCurrency.value.code,
            amount_rub: parseFloat(amount.value),
            crypto_amount: parseFloat(cryptoAmount.value),
        });

        if (response.data.success) {
            const url = response.data.data.payment_url;
            paymentUrl.value = url;

            // Очищаем форму
            amount.value = '';
        } else {
            errorMessage.value = response.data.message || 'Ошибка создания платежа';
        }
    } catch (error) {
        if (error.response?.data?.errors) {
            const errors = error.response.data.errors;
            const firstError = Object.values(errors)[0];
            errorMessage.value = Array.isArray(firstError) ? firstError[0] : firstError;
        } else if (error.response?.data?.message) {
            errorMessage.value = error.response.data.message;
        } else {
            errorMessage.value = 'Произошла ошибка. Попробуйте позже.';
        }
    } finally {
        isLoading.value = false;
    }
};

// Открыть ссылку на оплату
const openPaymentLink = () => {
    if (!paymentUrl.value) {
        console.error('Payment URL is empty!');
        errorMessage.value = 'Ссылка на оплату не найдена';
        return;
    }

    const url = paymentUrl.value;
    console.log('Payment URL:', url);

    // Проверяем что URL валидный
    if (!url || url === '') {
        console.error('Payment URL is invalid!');
        errorMessage.value = 'Некорректная ссылка на оплату';
        return;
    }

    const isTelegramLink = url.includes('t.me/');
    const tg = window.Telegram?.WebApp;

    try {
        if (tg && isTelegramLink) {
            console.log('Opening via openTelegramLink:', url);
            tg.openTelegramLink(url);
        } else if (tg) {
            console.log('Opening via openLink:', url);
            tg.openLink(url);
        } else {
            console.log('Opening via window.open:', url);
            window.open(url, '_blank');
        }
    } catch (e) {
        console.error('Error opening link:', e);
        // Фоллбэк - показываем ссылку для копирования
        errorMessage.value = 'Не удалось открыть ссылку. Скопируйте: ' + url;
    }
};

// Сбросить состояние для нового платежа
const resetPayment = () => {
    paymentUrl.value = null;
    errorMessage.value = '';
};

const getOption = (slotProps) => slotProps?.option ?? slotProps;

const methodSelect = ref(null);
const currencySelect = ref(null);
const isMethodSelectOpen = ref(false);
const isCurrencySelectOpen = ref(false);

const animateSelection = (selectRef) => {
    const el = selectRef.value?.$el;
    if (!el) return;

    gsap.fromTo(
        el,
        { scale: 0.99 },
        { scale: 1, duration: 0.18, ease: 'power2.out', clearProps: 'transform' },
    );
};

const onMethodSelectOpen = async () => {
    isMethodSelectOpen.value = true;
    await nextTick();
    animateDropdown(methodSelect);
};

const onCurrencySelectOpen = async () => {
    isCurrencySelectOpen.value = true;
    await nextTick();
    animateDropdown(currencySelect);
};

const animateDropdown = (selectRef) => {
    const root = selectRef.value?.$el;
    if (!root) return;

    const menu = root.querySelector('.vs__dropdown-menu');
    if (menu) {
        gsap.fromTo(
            menu,
            { autoAlpha: 0, y: 8, filter: 'blur(3px)' },
            {
                autoAlpha: 1,
                y: 0,
                filter: 'blur(0px)',
                duration: 0.22,
                ease: 'power2.out',
                clearProps: 'transform,filter',
            },
        );

        const options = menu.querySelectorAll('.vs__dropdown-option');
        if (options.length > 0) {
            gsap.from(options, {
                autoAlpha: 0,
                y: 6,
                duration: 0.2,
                ease: 'power2.out',
                stagger: 0.03,
                delay: 0.04,
                clearProps: 'transform',
            });
        }
    }
};

watch(selectedMethod, () => animateSelection(methodSelect));
watch(selectedCurrency, () => animateSelection(currencySelect));

onMounted(() => {
    loadPaymentData();
});
</script>

<template>
    <div class="flex flex-col gap-2.5">
        <!-- Загрузка данных -->
        <div v-if="isDataLoading" class="flex flex-col gap-2.5">
            <div class="h-9 rounded-full bg-[#272727] animate-pulse" />
            <div class="h-9 rounded-full bg-[#272727] animate-pulse" />
            <div class="h-9 rounded-full bg-[#272727] animate-pulse" />
            <div class="h-10 rounded-[10px] bg-[#272727] animate-pulse" />
        </div>

        <template v-else>
            <!-- Метод оплаты -->
            <div v-if="methods.length > 0" class="flex flex-col gap-1">
                <h1 class="text-[10px] text-white">Метод оплаты:</h1>
                <div
                    class="wallet-method-shell relative flex items-center justify-between rounded-full bg-[#272727]"
                    :class="{ 'wallet-select-open': isMethodSelectOpen }"
                >
                    <VSelect
                        ref="methodSelect"
                        v-model="selectedMethod"
                        :options="methods"
                        label="label"
                        :clearable="false"
                        :searchable="false"
                        :append-to-body="false"
                        class="wallet-method-select w-full p-1.5 pr-2.5"
                        @open="onMethodSelectOpen"
                        @close="isMethodSelectOpen = false"
                    >
                        <template #selected-option="slotProps">
                            <div class="flex items-center gap-2.5 text-xs text-white">
                                <img
                                    :src="getOption(slotProps)?.icon"
                                    class="size-6 rounded-full"
                                    alt=""
                                />
                                {{ getOption(slotProps)?.label }}
                            </div>
                        </template>

                        <template #option="slotProps">
                            <div class="flex items-center gap-2.5 text-xs text-white">
                                <img
                                    :src="getOption(slotProps)?.icon"
                                    class="size-6 rounded-full"
                                    alt=""
                                />
                                {{ getOption(slotProps)?.label }}
                            </div>
                        </template>

                        <template #open-indicator>
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="size-3"
                                viewBox="0 0 10 9"
                                fill="none"
                            >
                                <path
                                    d="M6.33312 7.5C5.56332 8.83333 3.63882 8.83333 2.86902 7.5L0.270947 3C-0.498854 1.66667 0.463398 2.00122e-08 2.003 -1.14584e-07L7.19915 -5.68846e-07C8.73875 -7.03442e-07 9.701 1.66667 8.9312 3L6.33312 7.5Z"
                                    fill="#4E4E4E"
                                />
                            </svg>
                        </template>
                    </VSelect>
                </div>
            </div>

            <!-- Криптовалюта -->
            <div v-if="currencies.length > 0" class="flex flex-col gap-1">
                <h1 class="text-[10px] text-white">Криптовалюта:</h1>
                <div
                    class="wallet-method-shell relative flex items-center justify-between rounded-full bg-[#272727]"
                    :class="{ 'wallet-select-open': isCurrencySelectOpen }"
                >
                    <VSelect
                        ref="currencySelect"
                        v-model="selectedCurrency"
                        :options="currencies"
                        label="label"
                        :clearable="false"
                        :searchable="false"
                        :append-to-body="false"
                        class="wallet-method-select w-full p-1.5 pr-2.5"
                        @open="onCurrencySelectOpen"
                        @close="isCurrencySelectOpen = false"
                    >
                        <template #selected-option="slotProps">
                            <div class="flex items-center gap-2.5 text-xs text-white">
                                {{ getOption(slotProps)?.label }}
                                <span v-if="getOption(slotProps)?.rate_to_rub" class="text-[#6CA243]">
                                    (1 = {{ getOption(slotProps)?.rate_to_rub?.toLocaleString('ru-RU', { maximumFractionDigits: 2 }) }} ₽)
                                </span>
                            </div>
                        </template>

                        <template #option="slotProps">
                            <div class="flex items-center justify-between gap-2.5 text-xs text-white w-full">
                                <span>{{ getOption(slotProps)?.label }}</span>
                                <span v-if="getOption(slotProps)?.rate_to_rub" class="text-[#6CA243] text-[10px]">
                                    {{ getOption(slotProps)?.rate_to_rub?.toLocaleString('ru-RU', { maximumFractionDigits: 2 }) }} ₽
                                </span>
                            </div>
                        </template>

                        <template #open-indicator>
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="size-3"
                                viewBox="0 0 10 9"
                                fill="none"
                            >
                                <path
                                    d="M6.33312 7.5C5.56332 8.83333 3.63882 8.83333 2.86902 7.5L0.270947 3C-0.498854 1.66667 0.463398 2.00122e-08 2.003 -1.14584e-07L7.19915 -5.68846e-07C8.73875 -7.03442e-07 9.701 1.66667 8.9312 3L6.33312 7.5Z"
                                    fill="#4E4E4E"
                                />
                            </svg>
                        </template>
                    </VSelect>
                </div>
            </div>

            <!-- Сумма пополнения -->
            <div class="flex flex-col gap-1">
                <h1 class="text-[10px] text-white">Сумма пополнения (RUB):</h1>
                <input
                    v-model="amount"
                    type="number"
                    class="rounded-full bg-[#272727] p-2.5 text-xs text-white outline-0 [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
                    placeholder="Введите сумму"
                    min="100"
                    max="1000000"
                />
                <div class="flex justify-between items-center">
                    <span class="text-[#333333] text-[10px]">Минимум: 100 RUB</span>
                    <span
                        v-if="amount && parseFloat(amount) > 0 && selectedCurrency"
                        class="text-[#6CA243] text-[10px]"
                    >
                        ≈ {{ cryptoAmount }} {{ selectedCurrency.code }}
                    </span>
                </div>
            </div>

            <!-- Сообщение об ошибке -->
            <div
                v-if="errorMessage"
                class="rounded-lg bg-red-500/20 p-2 text-center text-xs text-red-400"
            >
                {{ errorMessage }}
            </div>

            <!-- Ссылка на оплату (после создания) -->
            <div v-if="paymentUrl" class="flex flex-col gap-2.5">
                <div class="rounded-lg bg-[#6CA243]/20 p-3 text-center">
                    <p class="text-xs text-[#6CA243] mb-2">Счёт успешно создан!</p>
                    <p class="text-[10px] text-white/70 mb-2">Нажмите кнопку для перехода к оплате</p>
                </div>

                <button
                    class="main-btn flex items-center justify-center gap-2 w-full rounded-[10px] py-2.5 text-[10px] text-white"
                    @click="openPaymentLink"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 2L11 13" />
                        <path d="M22 2L15 22L11 13L2 9L22 2Z" />
                    </svg>
                    Оплатить в CryptoBot
                </button>

                <!-- Ссылка для копирования -->
                <div class="rounded-lg bg-[#1a1a1a] p-2">
                    <p class="text-[9px] text-white/40 mb-1">Или скопируйте ссылку:</p>
                    <p class="text-[10px] text-white/70 break-all select-all">{{ paymentUrl }}</p>
                </div>

                <button
                    class="w-full rounded-[10px] py-2 text-[10px] text-white/50 hover:text-white/70 transition-colors"
                    @click="resetPayment"
                >
                    Отмена
                </button>
            </div>

            <!-- Кнопка создания платежа -->
            <button
                v-else
                class="main-btn w-full rounded-[10px] py-2.5 text-[10px] text-white disabled:opacity-50 disabled:cursor-not-allowed"
                :disabled="!isValid || isLoading"
                @click="submitDeposit"
            >
                <span v-if="isLoading" class="flex items-center justify-center gap-2">
                    <svg class="animate-spin size-4" viewBox="0 0 24 24" fill="none">
                        <circle
                            class="opacity-25"
                            cx="12"
                            cy="12"
                            r="10"
                            stroke="currentColor"
                            stroke-width="4"
                        />
                        <path
                            class="opacity-75"
                            fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                        />
                    </svg>
                    Создание...
                </span>
                <span v-else>Пополнить</span>
            </button>
        </template>
    </div>
</template>

<style>
.wallet-method-shell {
    transition:
        box-shadow 180ms cubic-bezier(0.2, 0.8, 0.2, 1),
        background-color 180ms cubic-bezier(0.2, 0.8, 0.2, 1);
}

.wallet-method-shell.wallet-select-open {
    box-shadow:
        0 0 0 1px rgba(108, 162, 67, 0.5) inset,
        0 18px 40px rgba(0, 0, 0, 0.35);
}

.wallet-method-select {
    --vs-controls-color: #4e4e4e;
    --vs-border-color: transparent;
    --vs-dropdown-bg: #272727;
    --vs-dropdown-option-color: #ffffff;
    --vs-dropdown-option--active-bg: #333333;
    --vs-dropdown-option--active-color: #ffffff;
    --vs-selected-color: #ffffff;
    --vs-search-input-color: #ffffff;
}

.wallet-method-select .vs__dropdown-toggle {
    border: 0 !important;
    background: transparent !important;
    padding: 0 !important;
    min-height: 24px;
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    width: 100%;
    box-shadow: none !important;
    outline: none !important;
}

.wallet-method-select .vs__selected-options {
    padding: 0;
    flex: 1;
    min-width: 0;
    display: flex;
    align-items: center;
}

.wallet-method-select .vs__selected {
    margin: 0;
    padding: 0;
    opacity: 1 !important;
    color: #ffffff !important;
    background: transparent !important;
}

.wallet-method-select .vs__actions {
    padding: 0;
    margin-left: auto;
    display: flex;
    align-items: center;
}

.wallet-method-select .vs__clear {
    display: none;
}

.wallet-method-select .vs__search {
    display: none;
}

.wallet-method-select .vs__dropdown-menu {
    border: 0;
    box-shadow: 0 14px 40px rgba(0, 0, 0, 0.45);
    border-radius: 14px;
    overflow: hidden;
    padding: 6px;
    margin-top: 8px;
    z-index: 80;
    min-width: 100%;
    background: #272727;
}

.wallet-method-select .vs__dropdown-option {
    border-radius: 10px;
    padding: 10px 10px;
    line-height: 1;
    color: #ffffff !important;
}

.wallet-method-select .vs__dropdown-option--selected {
    background: rgba(108, 162, 67, 0.18);
}

.wallet-method-select .vs__dropdown-option--highlight {
    background: #333333;
}

.wallet-method-select .vs__open-indicator {
    transition: transform 180ms cubic-bezier(0.2, 0.8, 0.2, 1);
    transform-origin: 50% 50%;
}

.wallet-method-select.vs--open .vs__open-indicator {
    transform: rotate(180deg);
}

.wallet-method-select.vs--open .vs__dropdown-toggle {
    box-shadow: none !important;
}
</style>
