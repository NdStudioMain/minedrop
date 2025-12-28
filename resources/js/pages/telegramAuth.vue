<script setup>
import { onMounted } from 'vue'
import axios from 'axios'

onMounted(() => {
    if (!window.Telegram || !window.Telegram.WebApp) {
        window.location.href = 'https://t.me/MineDropBot'
        return
    }

    const initData = window.Telegram.WebApp.initData

    if (!initData) {
        toast.error('Telegram initData не найден')
        return
    }

    axios.post('/tg/auth/login', {
        initData: initData
    }).then(() => {
        window.location.href = '/'
    }).catch((e) => {
        console.error(e)
        toast.error('Ошибка авторизации')
    })
})
</script>

<template>
    <div class="flex items-center justify-center h-screen">
        <span>Авторизация через Telegram…</span>
    </div>
</template>
