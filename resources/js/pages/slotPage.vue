<script setup>
import gsap from 'gsap';
import { onBeforeUnmount, onMounted, ref } from 'vue';
import Layout from '../layouts/layout.vue';
import { defineProps } from 'vue';

const props = defineProps({
    game: {
        type: Object,
        required: true,
    },
});

const pageRoot = ref(null);
let pageCtx = null;

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
        <main ref="pageRoot" class="relative z-40 flex flex-col h-[calc(100vh-100px)] gap-4 px-2.5 pt-4">
            <div  class="flex flex-col gap-4 overflow-hidden rounded-2xl h-full">
                <iframe  :src="'/slots/'+game.id_game" class="w-full  h-full"></iframe>
            </div>
        </main>
    </Layout>
</template>
