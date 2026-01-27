<script setup>
import { onMounted, ref } from 'vue'
import axios from 'axios'

const status = ref('Авторизация через Telegram…')
const error = ref(null)

const getInitData = () => {
    // Способ 1: Из window.Telegram.WebApp.initData (основной)
    if (window.Telegram?.WebApp?.initData) {
        console.log('initData from window.Telegram.WebApp.initData')
        return window.Telegram.WebApp.initData
    }

    // Способ 2: Из URL хеша (tgWebAppData)
    const hash = window.location.hash
    if (hash) {
        const params = new URLSearchParams(hash.substring(1)) // Убираем #
        const tgWebAppData = params.get('tgWebAppData')
        if (tgWebAppData) {
            console.log('initData from URL hash tgWebAppData')
            return decodeURIComponent(tgWebAppData)
        }
    }

    // Способ 3: Из URL параметров (query string)
    const urlParams = new URLSearchParams(window.location.search)
    const tgWebAppData = urlParams.get('tgWebAppData')
    if (tgWebAppData) {
        console.log('initData from URL query tgWebAppData')
        return decodeURIComponent(tgWebAppData)
    }

    return null
}

const attemptLogin = async (retries = 3) => {
    // Ждём полной инициализации Telegram WebApp
    await new Promise(resolve => setTimeout(resolve, 100))

    if (!window.Telegram || !window.Telegram.WebApp) {
        console.error('Telegram WebApp не найден, редирект на бота')
        window.location.href = 'https://t.me/MineDropBot'
        return
    }

    // Вызываем ready() чтобы сообщить Telegram что WebApp готов
    window.Telegram.WebApp.ready()

    const initData = getInitData()

    console.log('initData:', initData)
    console.log('initData length:', initData?.length)
    console.log('window.Telegram.WebApp.initData:', window.Telegram.WebApp.initData)
    console.log('window.location.hash:', window.location.hash)

    if (!initData || initData.length < 10) {
        if (retries > 0) {
            console.log(`initData пустой, повторная попытка через 500мс (осталось ${retries})`)
            status.value = `Ожидание данных... (${retries})`
            await new Promise(resolve => setTimeout(resolve, 500))
            return attemptLogin(retries - 1)
        }

        console.error('Telegram initData не найден после всех попыток')
        error.value = 'Не удалось получить данные авторизации'
        status.value = 'Ошибка'
        return
    }

    try {
        status.value = 'Отправка данных...'

        const response = await axios.post('/tg/auth/login', {
            initData: initData
        }, {
            withCredentials: true,
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })

        if (response.data.success) {
            status.value = 'Успешно! Перенаправление...'
            window.location.href = response.data.redirect || '/'
        }
    } catch (e) {
        console.error('Login error:', e)
        error.value = e.response?.data?.message || 'Ошибка авторизации'
        status.value = 'Ошибка'
    }
}

onMounted(() => {
    attemptLogin()
})
</script>

<template>
    <div class="flex flex-col items-center justify-center h-screen gap-4 p-4">
        <div class="flex items-center gap-2">
            <svg v-if="!error" class="animate-spin h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span :class="error ? 'text-red-500' : ''">{{ status }}</span>
        </div>
        <div v-if="error" class="text-red-500 text-sm text-center">
            {{ error }}
        </div>
    </div>
</template>
