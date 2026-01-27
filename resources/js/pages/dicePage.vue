<script setup>
import gsap from 'gsap';
import { onBeforeUnmount, onMounted, ref, computed, watch } from 'vue';
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
    return (99.0 / chance.value).toFixed(4);
});

const isDragging = ref(false);
const isTypeChanging = ref(false);

watch(type, () => {
    isTypeChanging.value = true;
    setTimeout(() => {
        isTypeChanging.value = false;
    }, 50);
});

const greenStyle = computed(() => ({
    left: type.value === 'over' ? `${100 - chance.value}%` : '0%',
    width: `${chance.value}%`,
    transition: (isDragging.value || isTypeChanging.value) ? 'none' : 'all 0.3s ease',
}));

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

        setTimeout(() => {
            result.value = response.data;
            user.value.balance = response.data.newBalance;
            isRolling.value = false;
        }, 500);
    } catch (error) {
        console.error('Dice play failed:', error);
        toast.error(error.response?.data?.error || 'Play failed');
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
                                <div class="relative h-[9px] w-full rounded-[19px] bg-[#EF513C] overflow-visible">
                                    <!-- Настоящий range ПЕРВЫМ, чтобы он был под всем и работал на всей ширине -->
                                    <input
                                        ref="rangeInput"
                                        type="range"
                                        :value="type === 'over' ? 100 - chance : chance"
                                        @input="chance = type === 'over' ? 100 - Number($event.target.value) : Number($event.target.value)"
                                        min="1"
                                        max="99"
                                        step="1"
                                        class="absolute left-0 right-0 top-0 bottom-0 z-50 h-full w-full cursor-pointer"
                                        style="opacity: 0; -webkit-appearance: none; appearance: none; background: transparent; pointer-events: auto; margin: 0; padding: 0;"
                                        @mousedown="isDragging = true"
                                        @mouseup="isDragging = false"
                                        @touchstart="isDragging = true"
                                        @touchend="isDragging = false"
                                    />

                                    <div
                                        class="pointer-events-none absolute top-0 h-full rounded-[19px] bg-[#7AC73F]"
                                        :style="greenStyle"
                                    >
                                        <!-- декоративный индикатор как в макете -->
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            width="30"
                                            height="25"
                                            class="absolute -top-2 pointer-events-none"
                                            :class="type === 'over' ? '-left-4' : '-right-4'"
                                            viewBox="0 0 30 25"
                                            fill="none"
                                        >
                                            <rect width="30" height="25" rx="5" fill="#FDF1F3" />
                                            <path d="M8.88885 19.4445L8.88885 5.55557" stroke="#D5D5D5" stroke-width="2" stroke-linecap="round" />
                                            <path d="M15.5556 19.4445L15.5556 5.55557" stroke="#D5D5D5" stroke-width="2" stroke-linecap="round" />
                                            <path d="M22.2222 19.4445L22.2222 5.55557" stroke="#D5D5D5" stroke-width="2" stroke-linecap="round" />
                                        </svg>


                                    </div>
                                    <div v-if="result"
                                            class="absolute -top-8 -translate-x-1/2 transition-all duration-500 pointer-events-none"
                                            :style="{ left: result.roll + '%' }"
                                        >
                                            <div class="bg-white text-black px-2 py-1 rounded text-xs font-bold whitespace-nowrap">
                                                {{ result.roll }}
                                            </div>
                                        </div>
                                </div>
                            </div>
                        </div>
                        <div class="relative mt-4 flex flex-col gap-[2px] px-2">
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
</style>
