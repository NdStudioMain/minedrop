<script setup>
import { computed } from 'vue';
import { VueFinalModal } from 'vue-final-modal';
import gsap from 'gsap';

import WalletDeposit from './screens/WalletDeposit.vue';
import WalletTransactions from './screens/WalletTransactions.vue';
import WalletWithdraw from './screens/WalletWithdraw.vue';
import { useWalletModalStore } from '../../stores/walletModalStore';

const walletModal = useWalletModalStore();
const isOpen = walletModal.isOpen;
const activeScreen = walletModal.activeScreen;
const close = walletModal.close;
const setScreen = walletModal.setScreen;

const screenComponent = computed(() => {
    if (activeScreen.value === 'deposit') return WalletDeposit;
    if (activeScreen.value === 'withdraw') return WalletWithdraw;
    return WalletTransactions;
});

const onScreenEnter = (el, done) => {
    const target = el;
    gsap.fromTo(
        target,
        { autoAlpha: 0, y: 10 },
        {
            autoAlpha: 1,
            y: 0,
            duration: 0.28,
            ease: 'power2.out',
            clearProps: 'transform',
            onComplete: done,
        },
    );
};

const onScreenLeave = (el, done) => {
    const target = el;
    gsap.to(target, {
        autoAlpha: 0,
        y: -8,
        duration: 0.18,
        ease: 'power1.in',
        onComplete: done,
    });
};
</script>

<template>
    <VueFinalModal
        v-model="isOpen"
        class="flex items-start justify-center"
        overlay-class="bg-[#101010D9]"
        content-class="w-full max-w-[430px] px-2.5 pb-6 pt-16"
        overlay-transition="md-backdrop"
        content-transition="md-modal"
        :click-to-close="true"
        :esc-to-close="true"
    >
        <div class="flex w-full flex-col gap-4 rounded-[10px] bg-[#171717] p-4">
            <div class="flex flex-col gap-3">
                <div class="flex items-center justify-between">
                    <h1 class="text-xl text-white">Кошелек</h1>
                    <button
                        class="main-btn flex size-7 items-center justify-center rounded-sm"
                        type="button"
                        @click="close"
                    >
                        <svg
                            width="10"
                            height="10"
                            viewBox="0 0 10 10"
                            fill="none"
                            xmlns="http://www.w3.org/2000/svg"
                        >
                            <path
                                d="M0 10V8H2V10H0ZM8 10V8H10V10H8ZM2 8V6H4V8H2ZM6 8V6H8V8H6ZM4 6V4H6V6H4ZM2 4V2H4V4H2ZM6 4V2H8V4H6ZM0 2V0H2V2H0ZM8 2V0H10V2H8Z"
                                fill="#171717"
                            />
                        </svg>
                    </button>
                </div>
                <div class="flex gap-1">
                    <button
                        class="btn-select w-full rounded-full py-2.5 text-[10px]"
                        :class="{ active: activeScreen === 'deposit' }"
                        type="button"
                        @click="setScreen('deposit')"
                    >
                        Пополнить
                    </button>
                    <button
                        class="btn-select w-full rounded-full py-2.5 text-[10px]"
                        :class="{ active: activeScreen === 'withdraw' }"
                        type="button"
                        @click="setScreen('withdraw')"
                    >
                        Вывод
                    </button>
                    <button
                        class="btn-select w-full rounded-full py-2.5 text-[10px]"
                        :class="{ active: activeScreen === 'transactions' }"
                        type="button"
                        @click="setScreen('transactions')"
                    >
                        Транзакции
                    </button>
                </div>
            </div>

            <Transition mode="out-in" :css="false" @enter="onScreenEnter" @leave="onScreenLeave">
                <component :is="screenComponent" />
            </Transition>
        </div>
    </VueFinalModal>
</template>

<style>
/* VueFinalModal transitions (custom) */
.md-backdrop-enter-active,
.md-backdrop-leave-active {
    transition: opacity 220ms cubic-bezier(0.2, 0.8, 0.2, 1);
}

.md-backdrop-enter-from,
.md-backdrop-leave-to {
    opacity: 0;
}

.md-modal-enter-active {
    transition:
        opacity 200ms cubic-bezier(0.2, 0.8, 0.2, 1),
        transform 260ms cubic-bezier(0.16, 1, 0.3, 1),
        filter 260ms cubic-bezier(0.16, 1, 0.3, 1);
    will-change: transform, opacity, filter;
}

.md-modal-enter-from {
    opacity: 0;
    transform: translateY(-14px) scale(0.985);
    filter: blur(3px);
}

.md-modal-leave-active {
    transition:
        opacity 160ms cubic-bezier(0.4, 0, 1, 1),
        transform 200ms cubic-bezier(0.4, 0, 1, 1),
        filter 200ms cubic-bezier(0.4, 0, 1, 1);
    will-change: transform, opacity, filter;
}

.md-modal-leave-to {
    opacity: 0;
    transform: translateY(-8px) scale(0.99);
    filter: blur(2px);
}
</style>
