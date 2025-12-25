<script setup>
import gsap from 'gsap';
import { onBeforeUnmount, onMounted, ref } from 'vue';
import Layout from '../layouts/layout.vue';

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
        <main
            ref="pageRoot"
            class="relative z-40 flex flex-col gap-4 px-2.5 pt-4"
        >
            <div class="flex flex-col gap-2.5" data-animate>
                <h1 class="font-minecraft-ten text-2xl text-white" data-animate>
                    MINES
                </h1>
                <div v-for="i in 5" :key="i"
                    class="grid grid-cols-5 gap-2.5 rounded-[10px] bg-[url(/assets/img/bg-mines.png)] bg-cover bg-center p-2.5"
                    data-animate
                >
                    <template v-for="j in 5 " :key="j">
                    <button  v-if="j % 5 === i"
                        class="group relative w-full cursor-pointer overflow-hidden rounded-[5px] bg-[#272727]"
                    >
                        <img
                            src="/assets/img/bg-mine.png"
                            alt="mine"
                            class="h-full w-full object-cover opacity-0 transition duration-200"
                        />
                        <img
                            src="/assets/img/tnt-mine.png"
                            alt="mine"
                            class="absolute top-1/2 left-1/2 size-[80%] -translate-x-1/2 -translate-y-1/2 object-cover opacity-0 transition duration-200"
                        />
                        <img
                            src="/assets/img/emerald-mine.png"
                            alt="mine"
                            class="absolute top-1/2 left-1/2 size-[80%] -translate-x-1/2 -translate-y-1/2 object-cover transition duration-200"
                        />
                        <img
                            src="/assets/img/pick.png"
                            alt="pick"
                            class="pick-hover absolute top-1 right-1 z-20 size-[28px] translate-x-0 translate-y-0 rotate-0 object-cover opacity-0 group-hover:translate-x-[2px] group-hover:-translate-y-[2px] group-hover:rotate-10 group-hover:opacity-100"
                        />
                    </button>
                    <button
                        v-else
                        class="group relative w-full cursor-pointer overflow-hidden rounded-[5px] bg-[#272727]"
                    >
                        <img
                            src="/assets/img/bg-mine.png"
                            alt="mine"
                            class="h-full w-full object-cover"
                        />
                        <img
                            src="/assets/img/tnt-mine.png"
                            alt="mine"
                            class="absolute top-1/2 left-1/2 size-[30px] -translate-x-1/2 -translate-y-1/2 object-cover opacity-0 transition duration-200"
                        />
                        <img
                            src="/assets/img/emerald-mine.png"
                            alt="mine"
                            class="absolute top-1/2 left-1/2 h-[30px] w-[24px] -translate-x-1/2 -translate-y-1/2 object-cover opacity-0 transition duration-200"
                        />
                        <img
                            src="/assets/img/pick.png"
                            alt="pick"
                            class="pick-hover absolute top-1 right-1 z-20 size-[28px] translate-x-0 translate-y-0 rotate-0 object-cover opacity-0 group-hover:translate-x-[2px] group-hover:-translate-y-[2px] group-hover:rotate-10 group-hover:opacity-100"
                        />
                    </button>
                </template>
                </div>
                <div class="flex items-center gap-1" data-animate>
                    <button
                        type="button"
                        class="flex size-7 cursor-pointer items-center justify-center rounded-full bg-[#272727] text-[#4E4E4E] duration-300 ease-in-out hover:bg-[#333333] hover:text-white"
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
                    <div class="flex items-center gap-1">
                        <div v-for="k in 5" :key="k"
                            class="flex flex-col items-center justify-center rounded-[10px] bg-[#272727] gap-1.5 p-0.5 text-center text-[8px]/[100%] text-white duration-300 ease-in-out"
                        >
                            1.03x
                            <div
                                class="flex items-center justify-center rounded-full bg-[#171717] px-4 py-1.5 text-[8px]/[100%] text-[#4E4E4E]"
                            >
                                2 hit
                            </div>
                        </div>
                    </div>
                    <button
                        type="button"
                        class="flex size-7 cursor-pointer items-center justify-center rounded-full bg-[#272727] text-[#4E4E4E] duration-300 ease-in-out hover:bg-[#333333] hover:text-white"
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
            <div class="flex flex-col gap-2.5" data-animate>
                <div class="flex items-center gap-1" data-animate>
                    <button
                        type="button"
                        class="btn-select size-10 rounded-[10px] text-sm text-white"
                    >
                        1
                    </button>
                    <button
                        type="button"
                        class="btn-select active size-10 rounded-[10px] text-sm text-white"
                    >
                        2
                    </button>
                </div>
                <div class="flex items-center gap-1" data-animate>
                    <input
                        type="text"
                        name=""
                        class="w-full rounded-[10px] bg-white p-2.5 text-sm text-black outline-0 placeholder:text-black"
                        placeholder="Cумма ставки"
                        id=""
                    />
                    <button
                        type="button"
                        class="size-10 min-w-[40px] cursor-pointer rounded-[10px] bg-white text-sm text-black duration-300 ease-in-out hover:bg-white/80"
                    >
                        X2
                    </button>
                    <button
                        type="button"
                        class="size-10 min-w-[40px] cursor-pointer rounded-[10px] bg-white text-sm text-black duration-300 ease-in-out hover:bg-white/80"
                    >
                        /2
                    </button>
                </div>
                <div data-animate>
                    <button
                        type="button"
                        class="main-btn w-full rounded-[10px] py-2.5 text-sm text-white"

                    >
                        Начать игру
                    </button>
                </div>
            </div>
        </main>
    </Layout>
</template>

<style scoped>
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
