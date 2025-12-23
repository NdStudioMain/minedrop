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
        <main ref="pageRoot" class="relative z-40 flex flex-col gap-4 px-2.5 pt-4">
            <div class="flex flex-col gap-2.5" data-animate>
                <h1 class="text-xl text-white" data-animate>Dice</h1>
                <div
                    class="gap-7 flex flex-col rounded-[10px] bg-[url(/assets/img/bg-mines.png)] bg-cover bg-center p-2.5 pt-[50px]"
                    data-animate
                >
                    <div class="flex flex-col" data-animate>
                        <div class="rounded-[15px] bg-[#212121] px-1 py-1.5">
                            <div
                                class="rounded-[15px] bg-[#161616] px-1 py-2.5"
                            >
                                <div
                                    class="relative h-[9px] w-full rounded-[19px] bg-[#EF513C]"
                                >
                                    <div
                                        class="absolute top-0 left-0 h-full w-1/2 rounded-[19px] bg-[#7AC73F]"
                                    >
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            width="30"
                                            height="25"
                                            class="absolute -top-2 -right-4"
                                            viewBox="0 0 30 25"
                                            fill="none"
                                        >
                                            <rect
                                                width="30"
                                                height="25"
                                                rx="5"
                                                fill="#FDF1F3"
                                            />
                                            <path
                                                d="M8.88885 19.4445L8.88885 5.55557"
                                                stroke="#D5D5D5"
                                                stroke-width="2"
                                                stroke-linecap="round"
                                            />
                                            <path
                                                d="M15.5556 19.4445L15.5556 5.55557"
                                                stroke="#D5D5D5"
                                                stroke-width="2"
                                                stroke-linecap="round"
                                            />
                                            <path
                                                d="M22.2222 19.4445L22.2222 5.55557"
                                                stroke="#D5D5D5"
                                                stroke-width="2"
                                                stroke-linecap="round"
                                            />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div
                            class="relative -top-0.5 flex flex-col gap-[2px] px-6"
                        >
                            <div class="flex justify-between">
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="10"
                                    height="10"
                                    viewBox="0 0 10 10"
                                    fill="none"
                                >
                                    <path
                                        d="M6.39175 8.60594C5.68992 10.1956 3.43434 10.1956 2.73252 8.60594L0.172613 2.80779C-0.411088 1.48571 0.557032 9.88963e-07 2.00223 1.11531e-06L7.12204 1.56289e-06C8.56724 1.68924e-06 9.53536 1.48571 8.95166 2.80778L6.39175 8.60594Z"
                                        fill="#212121"
                                    />
                                </svg>
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="10"
                                    height="10"
                                    viewBox="0 0 10 10"
                                    fill="none"
                                >
                                    <path
                                        d="M6.39175 8.60594C5.68992 10.1956 3.43434 10.1956 2.73252 8.60594L0.172613 2.80779C-0.411088 1.48571 0.557032 9.88963e-07 2.00223 1.11531e-06L7.12204 1.56289e-06C8.56724 1.68924e-06 9.53536 1.48571 8.95166 2.80778L6.39175 8.60594Z"
                                        fill="#212121"
                                    />
                                </svg>
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="10"
                                    height="10"
                                    viewBox="0 0 10 10"
                                    fill="none"
                                >
                                    <path
                                        d="M6.39175 8.60594C5.68992 10.1956 3.43434 10.1956 2.73252 8.60594L0.172613 2.80779C-0.411088 1.48571 0.557032 9.88963e-07 2.00223 1.11531e-06L7.12204 1.56289e-06C8.56724 1.68924e-06 9.53536 1.48571 8.95166 2.80778L6.39175 8.60594Z"
                                        fill="#212121"
                                    />
                                </svg>
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="10"
                                    height="10"
                                    viewBox="0 0 10 10"
                                    fill="none"
                                >
                                    <path
                                        d="M6.39175 8.60594C5.68992 10.1956 3.43434 10.1956 2.73252 8.60594L0.172613 2.80779C-0.411088 1.48571 0.557032 9.88963e-07 2.00223 1.11531e-06L7.12204 1.56289e-06C8.56724 1.68924e-06 9.53536 1.48571 8.95166 2.80778L6.39175 8.60594Z"
                                        fill="#212121"
                                    />
                                </svg>
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="10"
                                    height="10"
                                    viewBox="0 0 10 10"
                                    fill="none"
                                >
                                    <path
                                        d="M6.39175 8.60594C5.68992 10.1956 3.43434 10.1956 2.73252 8.60594L0.172613 2.80779C-0.411088 1.48571 0.557032 9.88963e-07 2.00223 1.11531e-06L7.12204 1.56289e-06C8.56724 1.68924e-06 9.53536 1.48571 8.95166 2.80778L6.39175 8.60594Z"
                                        fill="#212121"
                                    />
                                </svg>
                            </div>
                            <div
                                class="flex justify-between text-center text-[12px] text-white"
                            >
                                <span class="flex w-2 justify-center"> 0 </span>
                                <span class="flex w-2 justify-center">
                                    25
                                </span>
                                <span class="flex w-2 justify-center">
                                    50
                                </span>
                                <span class="flex w-2 justify-center">
                                    75
                                </span>
                                <span class="flex w-2 justify-center">
                                    100
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="flex gap-1" data-animate>
                        <div
                            class="flex flex-col gap-1 text-left text-[12px] text-white"
                        >
                            <h1>Множитель</h1>
                            <input
                                type="text"
                                class="max-w-[88px] rounded-[10px] bg-[#272727] px-1.5 py-3 outline-0"
                                name=""
                                id=""
                                value="x1.98"
                            />
                        </div>
                        <div
                            class="flex w-full flex-col gap-1 text-left text-[12px] text-white"
                        >
                            <h1>Бросок больше</h1>
                            <div class="relative">
                                <input
                                    type="text"
                                    class="w-full rounded-[10px] bg-[#272727] px-1.5 py-3 outline-0"
                                    name=""
                                    id=""
                                    value="50.00"
                                />
                                <button
                                    class="absolute top-1/2 right-1.5 -translate-y-1/2 cursor-pointer duration-300 ease-in-out hover:opacity-65"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        width="20"
                                        height="20"
                                        viewBox="0 0 20 20"
                                        fill="none"
                                    >
                                        <path
                                            d="M10.0625 12.8958C9.65974 12.9097 9.27447 12.8439 8.90669 12.6983C8.53891 12.5528 8.21585 12.3408 7.93752 12.0625C7.65919 11.7842 7.44724 11.4717 7.30169 11.125C7.15613 10.7783 7.08335 10.4103 7.08335 10.0208C7.08335 9.88195 7.0903 9.74667 7.10419 9.61501C7.11808 9.48334 7.14585 9.35473 7.18752 9.22917C7.24308 9.06251 7.23974 8.89584 7.17752 8.72917C7.1153 8.56251 7.00752 8.44445 6.85419 8.37501C6.68752 8.30556 6.52447 8.30556 6.36502 8.37501C6.20558 8.44445 6.0978 8.56251 6.04169 8.72917C5.97224 8.93751 5.9203 9.14584 5.88585 9.35417C5.85141 9.56251 5.83391 9.77778 5.83335 10C5.83335 10.5556 5.94113 11.087 6.15669 11.5942C6.37224 12.1014 6.67419 12.5492 7.06252 12.9375C7.43752 13.3264 7.87863 13.625 8.38585 13.8333C8.89308 14.0417 9.41724 14.1528 9.95835 14.1667L9.60419 14.5208C9.47919 14.6458 9.41669 14.7917 9.41669 14.9583C9.41669 15.125 9.47919 15.2708 9.60419 15.3958C9.72919 15.5208 9.87502 15.5833 10.0417 15.5833C10.2084 15.5833 10.3542 15.5208 10.4792 15.3958L11.8125 14.0625C11.9792 13.8958 12.0625 13.7014 12.0625 13.4792C12.0625 13.2569 11.9792 13.0625 11.8125 12.8958L10.4792 11.5625C10.3542 11.4375 10.2084 11.375 10.0417 11.375C9.87502 11.375 9.72919 11.4375 9.60419 11.5625C9.47919 11.6875 9.41669 11.8333 9.41669 12C9.41669 12.1667 9.47919 12.3125 9.60419 12.4375L10.0625 12.8958ZM9.91669 7.08334C10.3195 7.08334 10.7084 7.15639 11.0834 7.30251C11.4584 7.44862 11.7847 7.66028 12.0625 7.93751C12.3403 8.21473 12.5522 8.52723 12.6984 8.87501C12.8445 9.22278 12.9172 9.59084 12.9167 9.97917C12.9167 10.1181 12.9097 10.2533 12.8959 10.385C12.882 10.5167 12.8542 10.6453 12.8125 10.7708C12.757 10.9375 12.7606 11.1075 12.8234 11.2808C12.8861 11.4542 12.9936 11.5758 13.1459 11.6458C13.3125 11.7153 13.4759 11.7153 13.6359 11.6458C13.7959 11.5764 13.9034 11.4583 13.9584 11.2917C14.0278 11.0833 14.08 10.8714 14.115 10.6558C14.15 10.4403 14.1672 10.2217 14.1667 10C14.1667 9.44445 14.0659 8.91334 13.8642 8.40667C13.6625 7.90001 13.3606 7.44501 12.9584 7.04167C12.5695 6.65278 12.1217 6.35778 11.615 6.15667C11.1084 5.95556 10.5839 5.85473 10.0417 5.85417L10.4167 5.47917C10.5278 5.35417 10.5834 5.20834 10.5834 5.04167C10.5834 4.87501 10.5209 4.72917 10.3959 4.60417C10.2709 4.47917 10.125 4.41667 9.95835 4.41667C9.79169 4.41667 9.64585 4.47917 9.52085 4.60417L8.18752 5.93751C8.02085 6.10417 7.93752 6.29862 7.93752 6.52084C7.93752 6.74306 8.02085 6.93751 8.18752 7.10417L9.52085 8.43751C9.64585 8.56251 9.79169 8.62501 9.95835 8.62501C10.125 8.62501 10.2709 8.56251 10.3959 8.43751C10.5209 8.31251 10.5834 8.16667 10.5834 8.00001C10.5834 7.83334 10.5209 7.68751 10.3959 7.56251L9.91669 7.08334ZM10 18.3333C8.84724 18.3333 7.76391 18.1145 6.75002 17.6767C5.73613 17.2389 4.85419 16.6453 4.10419 15.8958C3.35419 15.1464 2.76058 14.2645 2.32335 13.25C1.88613 12.2356 1.66724 11.1522 1.66669 10C1.66613 8.84778 1.88502 7.76445 2.32335 6.75001C2.76169 5.73556 3.3553 4.85362 4.10419 4.10417C4.85308 3.35473 5.73502 2.76112 6.75002 2.32334C7.76502 1.88556 8.84835 1.66667 10 1.66667C11.1517 1.66667 12.235 1.88556 13.25 2.32334C14.265 2.76112 15.147 3.35473 15.8959 4.10417C16.6447 4.85362 17.2386 5.73556 17.6775 6.75001C18.1164 7.76445 18.335 8.84778 18.3334 10C18.3317 11.1522 18.1128 12.2356 17.6767 13.25C17.2406 14.2645 16.647 15.1464 15.8959 15.8958C15.1447 16.6453 14.2628 17.2392 13.25 17.6775C12.2372 18.1158 11.1539 18.3344 10 18.3333Z"
                                            fill="#4E4E4E"
                                        />
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div
                            class="flex flex-col gap-1 text-left text-[12px] text-white"
                        >
                            <h1>Процент</h1>
                            <input
                                type="text"
                                class="max-w-[110px] rounded-[10px] bg-[#272727] px-1.5 py-3 outline-0"
                                name=""
                                id=""
                                value="50.00%"
                            />
                        </div>
                    </div>
                </div>
                <div class="flex flex-col gap-4" data-animate>
                    <button
                        type="button"
                        class="main-btn w-full rounded-[10px] py-2.5 text-sm text-white"
                    >
                        Сделать ставку
                    </button>
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
                </div>
            </div>
        </main>
    </Layout>
</template>
