import { reactive, readonly, toRefs } from 'vue';

const state = reactive({
    isOpen: false,
    activeScreen: 'deposit', // 'deposit' | 'withdraw' | 'transactions'
});

export function useWalletModalStore() {
    const open = (screen = 'deposit') => {
        state.activeScreen = screen;
        state.isOpen = true;
    };

    const close = () => {
        state.isOpen = false;
    };

    const setScreen = (screen) => {
        state.activeScreen = screen;
    };

    return {
        state: readonly(state),
        ...toRefs(state),
        open,
        close,
        setScreen,
    };
}


