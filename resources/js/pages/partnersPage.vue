<script setup>
import gsap from 'gsap'
import { onMounted, onBeforeUnmount, ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'
import Layout from '../layouts/layout.vue'
import { useAuthUser } from '@/composables/useAuthUser'

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

const { refCode, refBalance } = useAuthUser()

const BOT_USERNAME = 'minedropgamee_bot'

const referralLink = computed(() => {
    if (!refCode.value) return ''
    return `https://t.me/${BOT_USERNAME}?start=${refCode.value}`
})

const formattedRefBalance = computed(() =>
    new Intl.NumberFormat('ru-RU', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(refBalance.value)
)

const copied = ref(false)

const copyReferralLink = async () => {
    if (!referralLink.value) return

    try {
        await navigator.clipboard.writeText(referralLink.value)
        copied.value = true
        setTimeout(() => (copied.value = false), 1500)
    } catch (e) {
        console.error('Copy failed', e)
    }
}

const claiming = ref(false)

const claimReferralBalance = async () => {
    if (claiming.value || refBalance.value <= 0) return

    claiming.value = true

    try {
        await axios.post('/referral/claim')

        router.reload({
            only: ['auth'],
            preserveScroll: true,
        })
    } catch (e) {
        toast.error(e.response?.data?.message ?? 'Ошибка при зачислении')
    } finally {
        claiming.value = false
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
                class="relative flex h-[180px] w-full flex-col justify-between overflow-hidden rounded-[10px] px-2.5 py-4"
                data-animate
            >
                <div class="relative z-20 flex flex-col pl-1">
                    <h1 class="text-xl text-white">Реферальная система</h1>
                    <h2 class="text-xs text-[#878787]">
                        Получай до 10% с депозитов твоих друзей
                    </h2>
                </div>
                <div class="flex flex-col gap-2.5">
                    <h1 class="text-sm text-white">Реферальная ссылка</h1>
                    <div class="relative z-20 flex w-full gap-1">
                        <button
                            class="main-btn w-max rounded-[10px] px-6 py-2.5 text-[10px] text-white"
                            @click="copyReferralLink"
                        >
                            {{ copied ? 'Скопировано' : 'Скопировать реферальную ссылку' }}
                        </button>
                    </div>
                </div>
                <img
                    src="/assets/img/bg-partners-card-1.png"
                    class="absolute top-0 left-0 z-10 h-full w-full object-cover"
                    alt=""
                />
            </div>
            <div
                class="relative flex w-full flex-col justify-between gap-12 overflow-hidden rounded-[10px] bg-[#272727] px-2.5 py-4"
                data-animate
            >
                <div class="flex flex-col pl-1">
                    <h1 class="text-xl text-white">Ваш заработок</h1>
                    <h2 class="text-xs text-[#878787]">
                        Зарабаток с пополнений ваших друзей
                    </h2>
                </div>

                <div class="relative z-20 flex w-full gap-1">
                    <div class="text-ms rounded-[10px] bg-white p-2.5">
                        {{ formattedRefBalance }}
                    </div>
                    <button
                        class="main-btn relative w-full rounded-[10px] px-6 py-2.5 text-[10px] text-white"
                        :disabled="refBalance <= 0 || claiming"
                        @click="claimReferralBalance"
                    >
                        <img
                            src="/assets/img/gold.png"
                            class="absolute -top-6 left-0 h-[40px]"
                            alt=""
                            data-animate-image
                        />
                        {{ claiming ? 'Зачисление…' : 'Забрать' }}
                    </button>
                </div>
                <img
                    src="/assets/img/gold.png"
                    class="absolute top-0 right-3 h-[30px] -scale-x-100"
                    alt=""
                    data-animate-image
                />
                <img
                    src="/assets/img/gold-element.png"
                    class="absolute right-0 bottom-0 h-[160px]"
                    alt=""
                    data-animate-image
                />
            </div>
            <div class="flex flex-col gap-1" data-animate>
                <h1 class="text-[10px] text-white">Появились вопросы?</h1>
                <a href="https://t.me/helpminedrop" target="_blank" rel="noopener noreferrer"
                    class="main-btn w-full rounded-[10px] justify-center text-center py-2.5 text-[10px] text-white"
                >
                    Написать в поддержку
                </a>
            </div>
        </main>
    </Layout>
</template>
