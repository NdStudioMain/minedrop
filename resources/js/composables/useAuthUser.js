import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

export function useAuthUser() {
    const page = usePage()

    const user = computed(() => page.props.auth?.user ?? null)

    const isAuth  = computed(() => !!user.value)
    const balance = computed(() => user.value?.balance ?? 0)
    const avatar  = computed(() => user.value?.avatar ?? null)
    const name    = computed(() => user.value?.username ?? '')
    const refCode = computed(() => user.value?.ref_code ?? null)
    const refBalance = computed(() => user.value?.ref_balance ?? 0)

    return {
        user,
        isAuth,
        balance,
        avatar,
        name,
        refCode,
        refBalance
    }
}
