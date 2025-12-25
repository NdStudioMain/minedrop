<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import gsap from 'gsap';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const page = usePage();
const currentUrl = computed(() => page.url);

const navRoot = ref(null);
let navCtx = null;

onMounted(() => {
    navCtx = gsap.context(() => {
        const items = gsap.utils.toArray('[data-nav-item]');
        gsap.from(items, {
            autoAlpha: 0,
            y: 10,
            scale: 0.985,
            transformOrigin: '50% 50%',
            duration: 0.5,
            ease: 'power3.out',
            stagger: 0.06,
            clearProps: 'transform',
        });

        const navImage = navRoot.value?.querySelector('[data-nav-animate]');
        if (!navImage) return;

        gsap.from(navImage, {
            autoAlpha: 0,
            y: 16,
            x: 3,
            scale: 0.98,
            transformOrigin: '50% 50%',
            duration: 0.6,
            ease: 'power3.out',
            delay: 0.12,
            clearProps: 'transform',
        });
    }, navRoot.value ?? undefined);
});

onBeforeUnmount(() => {
    navCtx?.revert();
    navCtx = null;
});
</script>

<template>
    <nav ref="navRoot" class="fixed bottom-4 z-50 w-full px-2.5">
        <div
            class="flex w-full items-center justify-between rounded-[15px] border border-[#6C6C6C] bg-[#30303003] p-1 shadow-lg shadow-white/5 backdrop-blur-2xl"
        >
            <Link
                href="/"
                class="nav-btn"
                :class="{ active: currentUrl === '/' }"
            >
                <img src="/assets/img/earth.png" class="h-[22px]" alt="" />
                Главная
            </Link>
            <Link
                href="/bonus"
                class="nav-btn"
                :class="{ active: currentUrl === '/bonus' }"
            >
                <img src="/assets/img/diamond.png" class="h-[22px]" alt="" />
                Бонусы
            </Link>
            <Link
                href="/partners"
                class="nav-btn"
                :class="{ active: currentUrl === '/partners' }"
            >
                <img src="/assets/img/emerald.png" class="h-[22px]" alt="" />
                Партнерам
            </Link>
        </div>
        <img
            src="/assets/img/emerald-bottom.png"
            class="absolute -bottom-4 left-[47%] -z-10 h-11 -translate-x-1/2"
            alt=""
        />
    </nav>
</template>
