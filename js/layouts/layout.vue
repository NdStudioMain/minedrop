<script setup>
import { ModalsContainer } from 'vue-final-modal';
import Header from '../components/header.vue';
import WalletModal from '../components/modals/modal.vue';
import Nav from '../components/nav.vue';

</script>

<template>
    <div class="relative min-h-screen overflow-x-hidden">
        <Header />
        <slot />
        <Nav />
        <ModalsContainer />
        <WalletModal />

    </div>
</template>
