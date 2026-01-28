<script setup>
import axios from 'axios';
import { onMounted, ref } from 'vue';

const transactions = ref([]);
const isLoading = ref(true);
const currentPage = ref(1);
const totalPages = ref(1);

const statusIcons = {
    completed: {
        bg: 'bg-[#6CA24380]',
        icon: `<path d="M1 4.5L5 8.5L12.5 1" stroke="#9EFF55" stroke-width="2" stroke-linecap="round" />`,
        viewBox: '0 0 14 10',
    },
    pending: {
        bg: 'bg-[#FF9D0040]',
        icon: `<path d="M3.5 4.625L4.16 3.85719C4.7196 3.26943 5.39295 2.80168 6.13906 2.4824C6.88517 2.16312 7.68845 1.99899 8.5 2C11.8125 2 14.5 4.6875 14.5 8C14.5 11.3125 11.8125 14 8.5 14C7.25906 13.9999 6.04864 13.6152 5.03533 12.8989C4.02201 12.1826 3.2556 11.1698 2.84156 10" stroke="#FF9D00" stroke-width="2" stroke-miterlimit="10" stroke-linecap="round" /><path d="M1.49997 3.04438V6.50001C1.49997 6.63261 1.55265 6.75979 1.64641 6.85356C1.74018 6.94733 1.86736 7.00001 1.99997 7.00001H5.45559C5.90122 7.00001 6.12434 6.46157 5.80934 6.14657L2.3534 2.69063C2.0384 2.37501 1.49997 2.59876 1.49997 3.04438Z" fill="#FF9D00" />`,
        viewBox: '0 0 16 16',
    },
    failed: {
        bg: 'bg-[#FF150040]',
        icon: `<path d="M7 12H17" stroke="#FF493A" stroke-width="2" stroke-linecap="round" />`,
        viewBox: '0 0 24 24',
    },
};

const formatDate = (dateStr) => {
    const date = new Date(dateStr);
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    return `${day}.${month}.${year}/${hours}:${minutes}`;
};

const formatAmount = (amount) => {
    return parseFloat(amount).toLocaleString('ru-RU', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
};

const loadTransactions = async () => {
    isLoading.value = true;
    try {
        const response = await axios.get('/api/crypto-pay/payments');
        if (response.data.success) {
            transactions.value = response.data.data;
            totalPages.value = Math.ceil(transactions.value.length / 10) || 1;
        }
    } catch (error) {
        console.error('Ошибка загрузки транзакций:', error);
    } finally {
        isLoading.value = false;
    }
};

const prevPage = () => {
    if (currentPage.value > 1) currentPage.value--;
};

const nextPage = () => {
    if (currentPage.value < totalPages.value) currentPage.value++;
};

onMounted(() => {
    loadTransactions();
});
</script>

<template>
    <div class="flex flex-col gap-8">
        <!-- Загрузка -->
        <div v-if="isLoading" class="flex flex-col max-h-[400px] overflow-y-auto gap-1.5">
            <div
                v-for="i in 3"
                :key="i"
                class="relative flex items-center justify-between rounded-full bg-[#272727] p-1.5 pr-2.5 animate-pulse"
            >
                <div class="gap-2.5 flex items-center">
                    <div class="size-6 rounded-full bg-[#333333]" />
                    <div class="h-3 w-16 rounded bg-[#333333]" />
                    <div class="h-3 w-20 rounded bg-[#333333]" />
                </div>
                <div class="h-6 w-6 rounded-full bg-[#333333]" />
            </div>
                </div>

        <!-- Пустой список -->
                    <div
            v-else-if="transactions.length === 0"
            class="flex flex-col items-center justify-center py-8 text-center"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                class="size-12 text-[#333333] mb-2"
                            fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                        >
                            <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                                stroke-width="2"
                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                            />
                        </svg>
            <span class="text-[10px] text-[#4E4E4E]">Транзакций пока нет</span>
            </div>

        <!-- Список транзакций -->
        <div v-else class="flex flex-col gap-1.5">
            <div
                v-for="tx in transactions"
                :key="tx.id"
                class="relative flex max-h-[400px] overflow-y-auto items-center justify-between rounded-full bg-[#272727] p-1.5 pr-2.5"
            >
                <div class="gap-2.5 flex items-center">
                    <div class="flex items-center justify-between text-[8px] text-white gap-1">
                        <img
                            :src="tx.payment_system_icon || '/assets/img/cryptobot.png'"
                            class="size-6 rounded-full"
                            alt=""
                        />
                        #{{ tx.id }}
                    </div>
                    <span class="text-[8px] text-white">{{ formatAmount(tx.amount) }}</span>
                    <span class="text-[8px] text-white">{{ formatDate(tx.created_at) }}</span>
                </div>

                <div class="text-[8px] text-white flex items-center gap-1">
                    Пополнение
                    <div
                        class="size-6 flex items-center justify-center rounded-full"
                        :class="statusIcons[tx.status]?.bg || statusIcons.pending.bg"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            :width="tx.status === 'failed' ? 24 : tx.status === 'pending' ? 16 : 14"
                            :height="tx.status === 'failed' ? 24 : tx.status === 'pending' ? 16 : 10"
                            :viewBox="statusIcons[tx.status]?.viewBox || statusIcons.pending.viewBox"
                            fill="none"
                            v-html="statusIcons[tx.status]?.icon || statusIcons.pending.icon"
                            />
                    </div>
                </div>
            </div>
        </div>

        <!-- Пагинация -->
        <div
            v-if="transactions.length > 0"
            class="w-full flex justify-center items-center gap-4 text-xl/[15px] text-white"
        >
            <button
                type="button"
                class="bg-[#272727] rounded-full size-7 flex items-center justify-center text-[#4E4E4E] hover:bg-[#333333] hover:text-white ease-in-out duration-300 cursor-pointer disabled:opacity-30 disabled:cursor-not-allowed"
                :disabled="currentPage <= 1"
                @click="prevPage"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    width="9"
                    height="14"
                    viewBox="0 0 9 14"
                    fill="none"
                >
                    <path
                        d="M7.01431 12.2L1.41431 6.6L7.01431 1"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                    />
                </svg>
            </button>
            {{ currentPage }}
            <button
                type="button"
                class="bg-[#272727] rounded-full size-7 flex items-center justify-center text-[#4E4E4E] hover:bg-[#333333] hover:text-white ease-in-out duration-300 cursor-pointer disabled:opacity-30 disabled:cursor-not-allowed"
                :disabled="currentPage >= totalPages"
                @click="nextPage"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="rotate-180"
                    width="9"
                    height="14"
                    viewBox="0 0 9 14"
                    fill="none"
                >
                    <path
                        d="M7.01431 12.2L1.41431 6.6L7.01431 1"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                    />
                </svg>
            </button>
        </div>
    </div>
</template>
