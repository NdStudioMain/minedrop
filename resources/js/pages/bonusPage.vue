<script setup>
import gsap from 'gsap'
import axios from 'axios'
import { router } from '@inertiajs/vue3'
import { onMounted, onBeforeUnmount, ref } from 'vue'
import Layout from '../layouts/layout.vue'

const pageRoot = ref(null)
let pageCtx = null

onMounted(() => {
    pageCtx = gsap.context(() => {
        const items = gsap.utils.toArray('[data-animate]')
        gsap.from(items, {
            opacity: 0,
            y: 16,
            scale: 0.985,
            duration: 0.7,
            ease: 'power3.out',
            stagger: 0.08,
            clearProps: 'transform',
        })

        const images = gsap.utils.toArray('[data-animate-image]')
        gsap.from(images, {
            autoAlpha: 0,
            y: 50,
            x: 50,
            scale: 0.98,
            duration: 0.6,
            ease: 'power3.out',
            stagger: 0.06,
            delay: 0.08,
            clearProps: 'transform',
        })
    }, pageRoot.value)
})

onBeforeUnmount(() => {
    pageCtx?.revert()
    pageCtx = null
})

const claimingDaily = ref(false)

const promoCode = ref('')
const activating = ref(false)

const checking = ref(false)
const checkMessage = ref(null)

const claimDailyBonus = async () => {
    if (claimingDaily.value) return

    claimingDaily.value = true

    try {
        await axios.post('/bonus/daily')

        router.reload({
            only: ['auth'],
            preserveScroll: true,
        })
    } catch (e) {
        toast.error(e.response?.data?.message ?? 'Ошибка получения бонуса')
    } finally {
        claimingDaily.value = false
    }
}

const checkSubscriptions = async () => {
    if (checking.value) return

    checking.value = true
    checkMessage.value = null

    try {
        const res = await axios.post('/check/subscriptions')
        checkMessage.value = res.data.message
    } catch (e) {
        checkMessage.value =
            e.response?.data?.message ?? 'Ошибка проверки подписки'
    } finally {
        checking.value = false
    }
}

const activatePromo = async () => {
    if (!promoCode.value || activating.value) return

    activating.value = true

    try {
        await axios.post('/activate/promo', {
            code: promoCode.value,
        })

        promoCode.value = ''
        checkMessage.value = null

        router.reload({
            only: ['auth'],
            preserveScroll: true,
        })
    } catch (e) {
        toast.error(e.response?.data?.message ?? 'Ошибка при активации промокода')
    } finally {
        activating.value = false
    }
}
</script>

<template>
    <Layout>
        <main
            ref="pageRoot"
            class="relative z-40 flex flex-col gap-4 px-2.5 pt-4"
        >
            <img
                src="/assets/img/emerald-rotate.png"
                class="absolute top-1/2 -right-5 z-10 h-11 -translate-y-1/2 -scale-x-100"
                alt=""
                data-animate-image
            />
            <div
                class="relative flex h-[180px] w-full flex-col justify-between overflow-hidden rounded-[10px] bg-[#272727] px-2.5 py-4"
                data-animate
            >
                <div class="relative z-20 flex flex-col pl-1">
                    <h1 class="text-xl text-white">Ежедневный бонус</h1>
                    <h2 class="text-xs text-[#878787]">
                        Получай до 50 каждые 24ч
                    </h2>
                </div>
                <button
                    class="relative z-30 w-max rounded-[10px] bg-white px-6 py-2.5 duration-300 ease-in-out hover:opacity-80"
                    :disabled="claimingDaily"
                    @click="claimDailyBonus"
                >
                    {{ claimingDaily ? 'Получение…' : 'Получить' }}
                </button>
                <img
                    src="/assets/img/bg-bonus-card-1.png"
                    class="absolute top-0 left-0 z-10 h-full w-full object-cover"
                    alt=""
                />
                <img
                    src="/assets/img/Box.png"
                    class="absolute right-0 bottom-0 h-[160px]"
                    alt=""
                    data-animate-image
                />
            </div>
            <div
                class="relative flex w-full flex-col justify-between gap-2.5 overflow-hidden rounded-[10px] px-2.5 py-4"
                data-animate
            >
                <div class="relative z-20 flex flex-col pl-1">
                    <h1 class="text-xl text-white">Промокод</h1>
                    <h2 class="text-xs text-[#878787]">
                        Все промокоды выкладываются в нашем Telegram канале
                    </h2>
                </div>
                <div class="relative z-20 flex flex-col gap-1 pb-1">
                    <a
                        href="https://t.me/minedrop95"
                        target="_blank"
                        class="text-green-main underline duration-300 ease-in-out hover:text-green-main-hover"
                    >
                        https://t.me/minedrop95
                    </a>
                    <a
                        href="https://t.me/minedropreserve"
                        target="_blank"
                        class="text-green-main underline duration-300 ease-in-out hover:text-green-main-hover"
                    >
                        https://t.me/minedropreserve
                    </a>
                    <button
                        class="main-btn w-max rounded-[10px] px-6 py-2.5 text-[10px] text-white"
                        :disabled="checking"
                        @click="checkSubscriptions"
                    >
                        {{ checking ? 'Проверка…' : 'Проверить' }}
                    </button>
                    <p
                        v-if="checkMessage"
                        class="text-[10px] text-white opacity-80"
                    >
                        {{ checkMessage }}
                    </p>
                </div>
                <div class="relative z-20 flex w-full gap-1">
                    <input
                        v-model="promoCode"
                        type="text"
                        class="w-full rounded-[10px] bg-white p-2.5 text-sm outline-0"
                        placeholder="Введите промокод"
                    />

                    <button
                        class="main-btn relative w-max rounded-[10px] px-6 py-2.5 text-[10px] text-white"
                        :disabled="activating"
                        @click="activatePromo"
                    >
                        {{ activating ? 'Проверка…' : 'Активировать' }}
                    </button>
                </div>
                <img
                    src="/assets/img/tag.png"
                    class="absolute -top-4 right-3 z-10 h-[48px] -scale-x-100"
                    alt=""
                    data-animate-image
                />
                <img
                    src="/assets/img/bg-bonus-card-2.png"
                    class="absolute top-0 left-0 z-0 h-full w-full object-cover"
                    alt=""
                />
                <img
                    src="/assets/img/tag-element.png"
                    class="absolute right-0 bottom-0 z-10 h-[160px]"
                    alt=""
                    data-animate-image
                />
            </div>
            <div class="flex flex-col gap-1" data-animate>
                <h1 class="text-[10px] text-white">Появились вопросы?</h1>
                <button
                    class="main-btn w-full rounded-[10px] py-2.5 text-[10px] text-white"
                >
                    Написать в поддержку
                </button>
            </div>
        </main>
    </Layout>
</template>
