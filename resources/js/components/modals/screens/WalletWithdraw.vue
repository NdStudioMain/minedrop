<script setup>
import gsap from 'gsap';
import { nextTick, ref, watch } from 'vue';
import VSelect from 'vue-select';

const methods = [
    { label: 'Cryptobot', icon: 'assets/img/cryptobot.png' },
];

const selectedMethod = ref(methods[0]);

const getOption = (slotProps) => slotProps?.option ?? slotProps;

const methodSelect = ref(null);
const isSelectOpen = ref(false);

const animateSelection = () => {
    const el = methodSelect.value?.$el;
    if (!el) return;

    gsap.fromTo(
        el,
        { scale: 0.99 },
        { scale: 1, duration: 0.18, ease: 'power2.out', clearProps: 'transform' },
    );
};

const onSelectOpen = async () => {
    isSelectOpen.value = true;
    await nextTick();

    const root = methodSelect.value?.$el;
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

const onSelectClose = () => {
    isSelectOpen.value = false;
};

watch(selectedMethod, () => {
    animateSelection();
});
</script>

<template>
    <div class="flex flex-col gap-2.5">
        <div class="flex flex-col gap-1">
            <h1 class="text-[10px] text-white">Выберите метод:</h1>
            <div
                class="wallet-method-shell relative flex items-center justify-between rounded-full bg-[#272727]"
                :class="{ 'wallet-select-open': isSelectOpen }"
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
                    @open="onSelectOpen"
                    @close="onSelectClose"
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

        <div class="flex flex-col gap-1">
            <h1 class="text-[10px] text-white">Выберите метод:</h1>
            <input
                type="text"
                class="rounded-full bg-[#272727] p-2.5 text-xs text-white outline-0"
                placeholder="Введите сумму"
            />
            <span class="text-[#333333] text-[10px]">
                Минимальная сумма 100.00
            </span>
        </div>

        <button class="main-btn w-full rounded-[10px] py-2.5 text-[10px] text-white">
            Вывод
        </button>
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


