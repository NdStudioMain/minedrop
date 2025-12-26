<script setup>
import gsap from 'gsap';
import { onBeforeUnmount, onMounted, ref, computed } from 'vue';
import Layout from '../layouts/layout.vue';
import axios from 'axios';
import { useAuthUser } from '@/composables/useAuthUser';
import { dicePlay } from '@/actions/App/Http/Controllers/GameController';

const { user } = useAuthUser();

const pageRoot = ref(null);
let pageCtx = null;

const betAmount = ref(100);
const chance = ref(50);
const type = ref('over'); // over, under
const result = ref(null);
const isRolling = ref(false);

const multiplier = computed(() => {
    return (95 / chance.value).toFixed(2);
});

const rollDice = async () => {
    if (isRolling.value) return;
    isRolling.value = true;
    result.value = null;

    try {
        const response = await axios.post(dicePlay.url(), {
            bet: betAmount.value,
            chance: chance.value,
            type: type.value
        });

        // Add a small delay for animation effect
        setTimeout(() => {
            result.value = response.data;
            user.value.balance = response.data.newBalance;
            isRolling.value = false;
        }, 500);
    } catch (error) {
        console.error('Dice play failed:', error);
        alert(error.response?.data?.error || 'Play failed');
        isRolling.value = false;
    }
};

onMounted(() => {
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
        <main ref="pageRoot" class="relative z-40 flex flex-col gap-4 px-2.5 pt-4">
            <div class="flex flex-col gap-2.5" data-animate>
                <h1 class="text-xl text-white font-minecraft-ten" data-animate>Dice</h1>
                <div
                    class="gap-7 flex flex-col rounded-[10px] bg-[url(/assets/img/bg-mines.png)] bg-cover bg-center p-2.5 pt-[50px]"
                    data-animate
                >
                    <div class="flex flex-col" data-animate>
                        <div class="rounded-[15px] bg-[#212121] px-1 py-1.5">
                            <div class="rounded-[15px] bg-[#161616] px-1 py-2.5">
                                <div class="relative h-[9px] w-full rounded-[19px] bg-[#EF513C]">
                                    <div
                                        class="absolute top-0 h-full rounded-[19px] bg-[#7AC73F] transition-all duration-300"
                                        :style="{
                                            left: type === 'over' ? (100 - chance) + '%' : '0%',
                                            width: chance + '%'
                                        }"
                                    >
                                        <div v-if="result"
                                            class="absolute -top-8 -translate-x-1/2 transition-all duration-500"
                                            :style="{ left: result.roll + '%' }"
                                        >
                                            <div class="bg-white text-black px-2 py-1 rounded text-xs font-bold whitespace-nowrap">
                                                {{ result.roll }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="relative mt-4 flex flex-col gap-[2px] px-2">
                            <input
                                type="range"
                                v-model.number="chance"
                                min="1"
                                max="95"
                                class="w-full accent-blue-500"
                            />
                            <div class="flex justify-between text-center text-[12px] text-white mt-2">
                                <span>0</span>
                                <span>25</span>
                                <span>50</span>
                                <span>75</span>
                                <span>100</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-2" data-animate>
                        <div class="flex-1 flex flex-col gap-1 text-left text-[12px] text-white">
                            <h1>Множитель</h1>
                            <div class="rounded-[10px] bg-[#272727] px-3 py-3 font-bold">
                                x{{ multiplier }}
                            </div>
                        </div>
                        <div class="flex-1 flex flex-col gap-1 text-left text-[12px] text-white">
                            <h1>Шанс</h1>
                            <div class="rounded-[10px] bg-[#272727] px-3 py-3 font-bold">
                                {{ chance }}%
                            </div>
                        </div>
                        <div class="flex-1 flex flex-col gap-1 text-left text-[12px] text-white">
                            <h1>Тип</h1>
                            <button
                                @click="type = type === 'over' ? 'under' : 'over'"
                                class="rounded-[10px] bg-[#272727] px-3 py-3 font-bold uppercase hover:bg-white/10 transition-colors"
                            >
                                {{ type === 'over' ? '>' : '<' }} {{ type === 'over' ? (100 - chance).toFixed(2) : chance.toFixed(2) }}
                            </button>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col gap-4" data-animate>
                    <button
                        type="button"
                        @click="rollDice"
                        :disabled="isRolling"
                        class="main-btn w-full rounded-[10px] py-3 text-sm font-bold text-white transition-all"
                        :class="[
                            isRolling ? 'opacity-50 cursor-not-allowed' : '',
                            result?.isWin ? 'bg-green-600' : 'bg-blue-600'
                        ]"
                    >
                        {{ isRolling ? 'Бросок...' : 'Сделать ставку' }}
                    </button>

                    <div class="flex items-center gap-1" data-animate>
                        <input
                            type="number"
                            v-model.number="betAmount"
                            class="w-full rounded-[10px] bg-white p-2.5 text-sm text-black outline-0"
                            placeholder="Cумма ставки"
                        />
                        <button
                            type="button"
                            @click="betAmount *= 2"
                            class="size-10 min-w-[40px] cursor-pointer rounded-[10px] bg-white text-sm text-black hover:bg-white/80"
                        >
                            X2
                        </button>
                        <button
                            type="button"
                            @click="betAmount = Math.max(1, Math.floor(betAmount / 2))"
                            class="size-10 min-w-[40px] cursor-pointer rounded-[10px] bg-white text-sm text-black hover:bg-white/80"
                        >
                            /2
                        </button>
                    </div>
                </div>

                <div v-if="result" class="text-center mt-2 animate-bounce" data-animate>
                    <span v-if="result.isWin" class="text-green-500 font-bold text-lg">
                        ВЫИГРЫШ: +{{ result.winAmount }}
                    </span>
                    <span v-else class="text-red-500 font-bold">
                        ПРОИГРЫШ
                    </span>
                </div>
            </div>
        </main>
    </Layout>
</template>

<style scoped>
input[type=range] {
  -webkit-appearance: none;
  background: transparent;
}
input[type=range]::-webkit-slider-runnable-track {
  width: 100%;
  height: 8px;
  cursor: pointer;
  background: #272727;
  border-radius: 4px;
}
input[type=range]::-webkit-slider-thumb {
  height: 20px;
  width: 20px;
  border-radius: 50%;
  background: #ffffff;
  cursor: pointer;
  -webkit-appearance: none;
  margin-top: -6px;
  box-shadow: 0 0 10px rgba(0,0,0,0.5);
}
</style>
