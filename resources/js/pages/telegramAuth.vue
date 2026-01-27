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
        console.error('Telegram initData не найден')
        return
    }

    axios.post('/tg/auth/login', {
        initData: initData
    }, {
        withCredentials: true,
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    }).then((response) => {
        if (response.data.success) {
            window.location.href = response.data.redirect || '/'
        }
    }).catch((e) => {
        console.error(e)
    })
})
</script>

<template>
    <div class="flex items-center justify-center h-screen">
        <span>Авторизация через Telegram…</span>
    </div>
</template>
