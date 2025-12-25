<script setup>
import gsap from 'gsap';
import { Autoplay } from 'swiper/modules';
import { Swiper, SwiperSlide } from 'swiper/vue';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import Layout from '../layouts/layout.vue';
import { defineProps } from 'vue';

const props = defineProps({
    games: {
        type: Array,
        required: true,
    },
});

const pageRoot = ref(null);
let pageCtx = null;

const slides = [
    {
        key: 'tg',
        title: 'НАШ ТЕЛЕГРАМ',
        subtitle: 'Подписывайся и будь в курсе всех новостей!',
        titleColor: '#26A5E5',
        bg: 'assets/img/baner-1-bg.png',
        element: 'assets/img/baner-1-element.png',
        elementClass: 'absolute top-1/2 right-5 w-[124px] -translate-y-1/2',
        buttonText: 'Подписаться',
    },
    {
        key: 'mode',
        title: 'НОВЫЙ РЕЖИМ',
        subtitle: 'попытай удачу прямо сейчас!',
        titleColor: '#FFD900',
        bg: 'assets/img/baner-2-bg.png',
        element: 'assets/img/minedrop.png',
        elementClass: 'absolute top-1/2 right-5 w-[124px] -translate-y-1/2',
        buttonText: 'Играть',
    },
];

const autoplayDelayMs = 5000;
const activeIndex = ref(0);
const activeProgress = ref(0); // 0..1
const swiper = ref(null);

const onSwiper = (s) => {
    swiper.value = s;
    activeIndex.value = s.realIndex ?? s.activeIndex ?? 0;
    activeProgress.value = 0;
};

const onSlideChange = (s) => {
    activeIndex.value = s.realIndex ?? s.activeIndex ?? 0;
    activeProgress.value = 0;
};

const onAutoplayTimeLeft = (_, timeLeftMs) => {
    // Always compute elapsed progress (0..1) from timeLeft to avoid direction confusion.
    const elapsed = (autoplayDelayMs - timeLeftMs) / autoplayDelayMs;
    activeProgress.value = Math.max(0, Math.min(1, elapsed));
};

const goTo = (index) => {
    const s = swiper.value;
    if (!s) return;
    activeProgress.value = 0;
    if (s.params.loop) s.slideToLoop(index);
    else s.slideTo(index);
};

const indicators = computed(() =>
    Array.from({ length: slides.length }, (_, i) => ({
        i,
        // stories-like progress: previous = 1, active = progress, next = 0
        progress:
            i < activeIndex.value
                ? 1
                : i === activeIndex.value
                  ? activeProgress.value
                  : 0,
    })),
);

onMounted(() => {
    pageCtx = gsap.context(() => {
        const items = gsap.utils.toArray('[data-animate]');
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

        const images = gsap.utils.toArray('[data-animate-image]');
        gsap.from(images, {
            autoAlpha: 0,
            y: 50,
            x: 50,
            scale: 0.98,
            transformOrigin: '50% 50%',
            duration: 0.6,
            ease: 'power3.out',
            stagger: 0.06,
            delay: 0.08,
            clearProps: 'transform',
        });
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
            class="relative z-40 flex flex-col gap-4 px-2.5 pt-4"
        >
            <img
                src="/assets/img/emerald-rotate.png"
                class="absolute top-1/2 -right-5 z-10 h-11 -translate-y-1/2 -scale-x-100"
                alt=""
                data-animate-image
            />
            <div class="flex flex-col gap-1">
                <Swiper
                    class="w-full"
                    :modules="[Autoplay]"
                    :slides-per-view="1"
                    :loop="true"
                    :speed="650"
                    :autoplay="{
                        delay: autoplayDelayMs,
                        disableOnInteraction: false,
                        pauseOnMouseEnter: false,
                    }"
                    @swiper="onSwiper"
                    @slide-change="onSlideChange"
                    @autoplay-time-left="onAutoplayTimeLeft"
                    data-animate
                >
                    <SwiperSlide
                        v-for="s in slides"
                        :key="s.key"
                        class="w-full"
                    >
                        <div
                            class="relative flex h-44 flex-col justify-between overflow-hidden rounded-[10px] p-5"
                        >
                            <img
                                :src="s.bg"
                                class="absolute top-0 left-0 z-0 h-full w-full object-cover"
                                alt=""
                            />
                            <img
                                :src="s.element"
                                :class="s.elementClass"
                                alt=""
                            />
                            <div class="relative z-10 flex flex-col gap-2.5">
                                <h1
                                    class="font-minecraft-ten text-sm uppercase"
                                    :style="{ color: s.titleColor }"
                                >
                                    {{ s.title }}
                                </h1>
                                <h2
                                    class="max-w-[145px] text-[10px] text-white"
                                >
                                    {{ s.subtitle }}
                                </h2>
                            </div>
                            <button
                                class="relative z-10 w-max cursor-pointer rounded-[10px] bg-white px-6 py-2.5 duration-300 ease-in-out hover:opacity-80"
                            >
                                {{ s.buttonText }}
                            </button>
                        </div>
                    </SwiperSlide>
                </Swiper>

                <div class="flex w-full gap-1" data-animate>
                    <div
                        v-for="it in indicators"
                        :key="it.i"
                        class="relative h-1 w-full cursor-pointer overflow-hidden rounded-3xl bg-[#333333]"
                        role="button"
                        tabindex="0"
                        @click="goTo(it.i)"
                    >
                        <div
                            class="absolute top-0 left-0 h-full w-full origin-left rounded-3xl bg-green-main transition-transform duration-150 ease-out"
                            :style="{ transform: `scaleX(${it.progress})` }"
                        ></div>
                    </div>
                </div>
            </div>

            <div class="flex flex-col gap-4" data-animate>
                <h1 class="text-xl text-white">Наши игры</h1>
                <div class="flex max-w-[350px] gap-2.5 overflow-x-auto">
                    <Link v-for="game in games" :key="game.id" :href="`/${game.url_slug}`"
                        class="group relative h-[144px] min-w-[110px] cursor-pointer overflow-hidden rounded-[5px] duration-300 ease-in-out"
                    >
                        <div
                            class="absolute top-0 left-0 z-20 flex h-full w-full items-center justify-center bg-black/85 opacity-0 duration-300 ease-in-out group-hover:opacity-100"
                        >
                            <svg
                                width="50"
                                height="53"
                                viewBox="0 0 50 53"
                                fill="none"
                                xmlns="http://www.w3.org/2000/svg"
                            >
                                <circle cx="25" cy="28" r="25" fill="#4F7632" />
                                <circle cx="25" cy="25" r="25" fill="#6CA243" />
                                <path
                                    d="M35.8761 23.5728C36.4454 23.8533 36.9277 24.2834 37.2713 24.817C37.6148 25.3506 37.8067 25.9676 37.8264 26.602C37.8461 27.2363 37.6928 27.864 37.383 28.4179C37.0732 28.9718 36.6185 29.431 36.0677 29.7462L21.3796 38.3341C19.0146 39.7183 16.0069 38.1075 15.9233 35.4149L15.419 19.1652C15.3354 16.4715 18.2376 14.6785 20.6838 15.911L35.8761 23.5728Z"
                                    fill="#AFAFAF"
                                />
                                <path
                                    d="M35.8761 21.5728C36.4454 21.8533 36.9277 22.2834 37.2713 22.817C37.6148 23.3506 37.8067 23.9676 37.8264 24.602C37.8461 25.2363 37.6928 25.864 37.383 26.4179C37.0732 26.9718 36.6185 27.431 36.0677 27.7462L21.3796 36.3341C19.0146 37.7183 16.0069 36.1075 15.9233 33.4149L15.419 17.1652C15.3354 14.4715 18.2376 12.6785 20.6838 13.911L35.8761 21.5728Z"
                                    fill="white"
                                />
                            </svg>
                        </div>
                        <img
                            :src="'/storage/' + game.image"
                            class="absolute bottom-0 left-0 h-full w-full"
                            alt=""
                        />
                    </Link>

                </div>
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
