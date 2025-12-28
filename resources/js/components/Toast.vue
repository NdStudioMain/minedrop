<template>
    <Transition name="toast">
        <div
            v-if="visible"
            class="p-2.5 flex items-center gap-2.5 bg-[#272727] rounded-full relative max-w-[245px] overflow-hidden shadow-lg"
        >
            <div
                class="size-14 rounded-full blur-[33px] -left-2 -bottom-7 absolute"
                :class="type === 'error' ? 'bg-[#561B16]' : 'bg-[#6CA24380]'"
            ></div>

            <div
                class="size-9 flex items-center justify-center rounded-full flex-shrink-0"
                :class="type === 'error' ? 'bg-[#561B16]' : 'bg-[#6CA24380]'"
            >
                <svg
                    v-if="type === 'error'"
                    xmlns="http://www.w3.org/2000/svg"
                    width="32"
                    height="32"
                    viewBox="0 0 24 24"
                    fill="none"
                >
                    <path
                        d="M7 12H17"
                        stroke="#FF493A"
                        stroke-width="2"
                        stroke-linecap="round"
                    />
                </svg>
                <svg
                    v-else
                    xmlns="http://www.w3.org/2000/svg"
                    width="20"
                    height="15"
                    viewBox="0 0 20 15"
                    fill="none"
                >
                    <path
                        d="M1.5 6.60417L7.33333 12.4375L18.2708 1.5"
                        stroke="#9EFF55"
                        stroke-width="3"
                        stroke-linecap="round"
                    />
                </svg>
            </div>

            <div class="flex flex-col min-w-0">
                <span class="text-white text-xs font-medium">
                    {{ title }}
                </span>
                <span class="text-[#878787] text-[10px]">
                    {{ message }}
                </span>
            </div>
        </div>
    </Transition>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';

const props = defineProps({
    type: {
        type: String,
        default: 'success',
        validator: (value) => ['success', 'error'].includes(value),
    },
    title: {
        type: String,
        default: '',
    },
    message: {
        type: String,
        default: '',
    },
    duration: {
        type: Number,
        default: 3000,
    },
    onClose: {
        type: Function,
        default: null,
    },
});

const visible = ref(false);
let timeoutId = null;

function close() {
    visible.value = false;
    if (props.onClose) {
        setTimeout(() => {
            props.onClose();
        }, 300);
    }
}

onMounted(() => {
    // Small delay to trigger animation
    setTimeout(() => {
        visible.value = true;
    }, 10);

    if (props.duration > 0) {
        timeoutId = setTimeout(() => {
            close();
        }, props.duration);
    }
});

onBeforeUnmount(() => {
    if (timeoutId) {
        clearTimeout(timeoutId);
    }
});
</script>

<style scoped>
.toast-enter-active,
.toast-leave-active {
    transition: all 0.3s ease;
}

.toast-enter-from {
    opacity: 0;
    transform: translateY(-100%);
}

.toast-leave-to {
    opacity: 0;
    transform: translateY(-100%);
}
</style>

