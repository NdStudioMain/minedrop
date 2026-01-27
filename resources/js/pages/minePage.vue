<script setup>
import gsap from 'gsap';
import { onBeforeUnmount, onMounted, ref, computed, watch } from 'vue';
import Layout from '../layouts/layout.vue';
import axios from 'axios';
import { useAuthUser } from '@/composables/useAuthUser';
import { minesStart, minesPick, minesCashout, minesMultipliers, minesState } from '@/actions/App/Http/Controllers/GameController';

const { user } = useAuthUser();

const isLoading = ref(true);

const pageRoot = ref(null);
let pageCtx = null;

const betAmount = ref(100);
const mineCount = ref(3);
const gameStatus = ref('idle'); // idle, playing, lost, won
const revealedCells = ref([]);
const multiplier = ref(0);
const multipliers = ref([]);
const nextMultiplier = ref(0);
const mines = ref([]);
const lastWinAmount = ref(0);

const canCashout = computed(() => gameStatus.value === 'playing' && revealedCells.value.length > 0);

const fetchMultipliers = async () => {
    try {
        const response = await axios.post(minesMultipliers.url(), {
            bet: betAmount.value,
            mines: mineCount.value
        });
        multipliers.value = response.data.multipliers;
    } catch (error) {
        console.error('Failed to fetch multipliers:', error);
    }
};

watch([betAmount, mineCount], () => {
    if (gameStatus.value === 'idle' || gameStatus.value === 'won' || gameStatus.value === 'lost') {
        fetchMultipliers();
    }
});

const restoreState = async () => {
    try {
        const response = await axios.get(minesState.url());
        const state = response.data.state;

        if (state) {
            gameStatus.value = state.status;
            betAmount.value = state.bet;
            mineCount.value = state.mineCount;
            revealedCells.value = state.revealed;
            multiplier.value = state.multiplier;
            nextMultiplier.value = state.nextMultiplier;
            multipliers.value = state.multipliers;
        } else {
            await fetchMultipliers();
        }
    } catch (error) {
        console.error('Failed to restore state:', error);
        await fetchMultipliers();
    } finally {
        isLoading.value = false;
    }
};

const startGame = async () => {
    try {
        const response = await axios.post(minesStart.url(), {
            bet: betAmount.value,
            mines: mineCount.value
        });

        gameStatus.value = response.data.status;
        revealedCells.value = response.data.revealed;
        nextMultiplier.value = response.data.nextMultiplier;
        multipliers.value = response.data.multipliers;
        multiplier.value = 0;
        mines.value = [];
        user.value.balance = response.data.newBalance;
    } catch (error) {
        console.error('Failed to start game:', error);
        toast.error(error.response?.data?.error || 'Failed to start game');
    }
};

const pickCell = async (cellId) => {
    if (gameStatus.value !== 'playing') return;
    if (revealedCells.value.includes(cellId)) return;

    try {
        const response = await axios.post(minesPick.url(), { cellId });

        if (response.data.status === 'lost') {
            gameStatus.value = 'lost';
            mines.value = response.data.mines;
            revealedCells.value.push(cellId);
            user.value.balance = response.data.newBalance;
        } else {
            gameStatus.value = response.data.status;
            revealedCells.value = response.data.revealed;
            multiplier.value = response.data.multiplier;
            nextMultiplier.value = response.data.nextMultiplier;
            multipliers.value = response.data.multipliers;
        }
    } catch (error) {
        console.error('Failed to pick cell:', error);
    }
};

const cashout = async () => {
    if (!canCashout.value) return;

    try {
        const response = await axios.post(minesCashout.url());
        gameStatus.value = 'won';
        lastWinAmount.value = response.data.winAmount;
        multiplier.value = response.data.multiplier;
        mines.value = response.data.mines;
        user.value.balance = response.data.newBalance;
        fetchMultipliers();
    } catch (error) {
        console.error('Failed to cashout:', error);
    }
};

onMounted(() => {
    restoreState();
    pageCtx = gsap.context(() => {
        const items = gsap.utils.toArray('[data-animate]');
        if (items.length > 0) {
            gsap.from(items, {
                opacity: 0,
                y: 16,
                scale: 0.985,
                transformOrigin: '50% 50%',
                duration: 0.7,
                ease: 'power3.out',
                stagger: 0.08,
                clearProps: 'transform',
            });
        }
    }, pageRoot.value ?? undefined);
});

onBeforeUnmount(() => {
    pageCtx?.revert();
    pageCtx = null;
});
</script>

<template>
    <Layout>
        <main
            ref="pageRoot"
            class="relative z-40 flex flex-col gap-4 pb-25 px-2.5 pt-4"
        >
            <!-- Loading state -->
            <div v-if="isLoading" class="flex items-center justify-center py-20">
                <div class="h-8 w-8 animate-spin rounded-full border-4 border-white/20 border-t-white"></div>
            </div>

            <template v-else>
            <div class="flex flex-col gap-2.5" data-animate>
                <div class="flex items-center justify-between" data-animate>
                    <h1 class="font-minecraft-ten text-2xl text-white">
                        MINES
                    </h1>
                    <div class="flex items-center gap-2 rounded-[10px] bg-[#272727] px-3 py-1.5">
                        <span class="text-xs text-[#4E4E4E]">Мин:</span>
                        <span class="text-sm font-bold text-white">{{ mineCount }}</span>
                    </div>
                </div>
                <div
                    class="grid grid-cols-5 gap-2.5 rounded-[10px]  bg-[url(/assets/img/bg-mines.png)] bg-cover bg-center p-2.5"
                    data-animate
                >
                    <template v-for="i in 25" :key="i">
                        <button
                            @click="pickCell(i-1)"
                            class="group relative block_mine aspect-square w-full cursor-pointer overflow-hidden rounded-[5px] bg-[#272727]"
                            :disabled="gameStatus !== 'playing' || revealedCells.includes(i-1)"
                        >
                            <img
                                src="/assets/img/bg-mine.png"
                                alt="mine"
                                class="h-full w-full object-cover transition duration-200"
                                :class="revealedCells.includes(i-1) || gameStatus === 'won' || gameStatus === 'lost' ? 'opacity-0' : 'opacity-100'"
                            />

                            <!-- Mine (using Box.png as placeholder if tnt-mine.png is missing) -->
                            <div
                                class="absolute top-1/2 left-1/2 size-[80%] -translate-x-1/2 -translate-y-1/2 transition duration-200 flex items-center justify-center"
                                :class="(revealedCells.includes(i-1) && mines.includes(i-1)) || (gameStatus !== 'playing' && mines.includes(i-1)) ? 'opacity-100' : 'opacity-0'"
                            >
                                <img
                                    src="/assets/img/Box.png"
                                    alt="mine"
                                    class="size-full object-contain"
                                />
                            </div>

                            <!-- Emerald (Safe) -->
                            <img
                                src="/assets/img/emerald-mine.png"
                                alt="safe"
                                class="absolute top-1/2 left-1/2 size-[80%] -translate-x-1/2 -translate-y-1/2 object-cover transition duration-200"
                                :class="revealedCells.includes(i-1) && !mines.includes(i-1) ? 'opacity-100' : 'opacity-0'"
                            />

                            <img
                                v-if="gameStatus === 'playing' && !revealedCells.includes(i-1)"
                                src="/assets/img/pick.png"
                                alt="pick"
                                class="pick-hover absolute top-1 right-1 z-20 size-[28px] translate-x-0 translate-y-0 rotate-0 object-cover opacity-0 group-hover:translate-x-[2px] group-hover:-translate-y-[2px] group-hover:rotate-10 group-hover:opacity-100"
                            />
                        </button>
                    </template>
                </div>

                <div class="flex items-center gap-1 overflow-x-auto pb-2 scrollbar-hide" data-animate>
                    <div class="flex items-center gap-1">
                        <div v-for="m in multipliers" :key="m.step"
                            class="flex flex-col items-center justify-center rounded-[10px] gap-1.5 p-1.5 text-center text-[10px] text-white duration-300 min-w-[60px] border"
                            :class="[
                                m.step === revealedCells.length ? 'bg-green-600/20 border-green-600/50' :
                                m.step === revealedCells.length + 1 && gameStatus === 'playing' ? 'bg-blue-600/20 border-blue-600/50' :
                                'bg-[#272727] border-transparent opacity-50'
                            ]"
                        >
                            {{ m.multiplier }}x
                            <div class="flex items-center justify-center rounded-full px-3 py-1 text-[8px] whitespace-nowrap"
                                :class="[
                                    m.step === revealedCells.length ? 'bg-green-600/40 text-white' :
                                    m.step === revealedCells.length + 1 && gameStatus === 'playing' ? 'bg-blue-600/40 text-white' :
                                    'bg-[#171717] text-[#4E4E4E]'
                                ]"
                            >
                                {{ m.step }} HIT
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex flex-col gap-2.5" data-animate>
                <div class="flex items-center gap-1" data-animate>
                    <button
                        v-for="m in [1, 3, 5, 10, 24]"
                        :key="m"
                        type="button"
                        @click="mineCount = m"
                        class="btn-select flex-1 h-10 rounded-[10px] text-sm text-white transition-colors"
                        :class="[mineCount === m ? '!bg-white !text-black' : 'bg-[#272727]' ]"
                    >
                        {{ m }}
                    </button>
                </div>
                <div class="flex items-center gap-1" data-animate>
                    <input
                        type="number"
                        v-model.number="betAmount"
                        class="w-full rounded-[10px] bg-white p-2.5 text-sm text-black outline-0 placeholder:text-gray-500"
                        placeholder="Сумма ставки"
                    />
                    <button
                        type="button"
                        @click="betAmount *= 2"
                        class="size-10 min-w-[40px] cursor-pointer rounded-[10px] bg-white text-sm text-black duration-300 ease-in-out hover:bg-white/80"
                    >
                        X2
                    </button>
                    <button
                        type="button"
                        @click="betAmount = Math.max(1, Math.floor(betAmount / 2))"
                        class="size-10 min-w-[40px] cursor-pointer rounded-[10px] bg-white text-sm text-black duration-300 ease-in-out hover:bg-white/80"
                    >
                        /2
                    </button>
                </div>
                <div data-animate>
                    <button
                        v-if="gameStatus !== 'playing'"
                        type="button"
                        @click="startGame"
                        class="main-btn w-full rounded-[10px] py-3 text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 transition-colors"
                    >
                        Начать игру
                    </button>
                    <button
                        v-else
                        type="button"
                        @click="cashout"
                        class="main-btn w-full rounded-[10px] py-3 text-sm font-bold text-white bg-green-600 hover:bg-green-700 transition-colors disabled:opacity-50"
                        :disabled="!canCashout"
                    >
                        Забрать {{ (betAmount * multiplier).toFixed(2) }}
                    </button>
                </div>
                <div v-if="gameStatus === 'won'" class="text-center text-green-500 font-bold" data-animate>
                    Вы выиграли {{ lastWinAmount }}!
                </div>
                <div v-if="gameStatus === 'lost'" class="text-center text-red-500 font-bold" data-animate>
                    Вы проиграли!
                </div>
            </div>
            </template>
        </main>
    </Layout>
</template>

<style scoped>
.scrollbar-hide::-webkit-scrollbar {
    display: none;
}

.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}


.pick-hover {
    animation: pick-hover 0.6s ease-in-out infinite;
}

@keyframes pick-hover {
    0% {
        transform: translate(-2px, 2px) rotate(-20deg) scale(0.95);
    }
    20% {
        transform: translate(-1px, -1px) rotate(0deg) scale(1);
    }
    50% {
        transform: translate(-2px, 2px) rotate(-20deg) scale(0.95);
    }
    75% {
        transform: translate(-1px, -1px) rotate(0deg) scale(1);
    }
    100% {
        transform: translate(-2px, 2px) rotate(-20deg) scale(0.95);
    }
}
</style>
