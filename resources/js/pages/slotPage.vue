<script setup>
    import gsap from 'gsap';
    import { onBeforeUnmount, onMounted, ref, computed } from 'vue';
    import { router } from '@inertiajs/vue3';
    import Layout from '../layouts/layout.vue';
    import { defineProps } from 'vue';

    const domain = window.location.host.replace(/^https?:\/\//, '');
    const props = defineProps({
        game: {
            type: Object,
            required: true,
        },
        session: {
            type: String,
            required: true,
        },
    });

    const iframeSrc = computed(() => {
        return `/slots/${props.game.id_game}/?sessionID=${props.session}&rgs_url=${domain}&lang=ru&currency=RUB&device=desktop&social=false`;
    });

    const pageRoot = ref(null);
    const iframeRef = ref(null);
    let pageCtx = null;
    let balanceUpdateInterval = null;

    const updateBalance = () => {
        router.reload({
            only: ['auth'],
            preserveScroll: true,
            preserveState: true,
        });
    };

    const handleBackButton = () => {
        router.visit('/');
    };

    onMounted(() => {
        if (window.Telegram?.WebApp?.BackButton) {
            window.Telegram.WebApp.BackButton.show();
            window.Telegram.WebApp.BackButton.onClick(handleBackButton);
        }

        balanceUpdateInterval = setInterval(() => {
            updateBalance();
        }, 1000);

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
        if (window.Telegram?.WebApp?.BackButton) {
            window.Telegram.WebApp.BackButton.offClick(handleBackButton);
            window.Telegram.WebApp.BackButton.hide();
        }

        if (balanceUpdateInterval) {
            clearInterval(balanceUpdateInterval);
            balanceUpdateInterval = null;
        }

        pageCtx?.revert();
        pageCtx = null;
    });
    </script>

    <template>
        <main ref="pageRoot" class="relative z-40 flex flex-col h-screen">
            <iframe
                ref="iframeRef"
                :src="iframeSrc"
                class="w-full h-screen"
            ></iframe>
        </main>
    </template>