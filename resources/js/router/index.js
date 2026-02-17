import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes = [
    {
        path: '/login',
        name: 'login',
        component: () => import('@/pages/LoginPage.vue'),
        meta: { guest: true },
    },
    {
        path: '/',
        component: () => import('@/layouts/AppLayout.vue'),
        meta: { auth: true },
        children: [
            {
                path: '',
                name: 'dashboard',
                component: () => import('@/pages/DashboardPage.vue'),
            },
            {
                path: 'habits',
                name: 'habits',
                component: () => import('@/pages/habits/HabitsPage.vue'),
                meta: { module: 'habits' },
            },
            {
                path: 'habits/week',
                name: 'habits-week',
                component: () => import('@/pages/habits/HabitsWeekPage.vue'),
                meta: { module: 'habits' },
            },
            {
                path: 'habits/calendar',
                name: 'habits-calendar',
                component: () => import('@/pages/habits/HabitsCalendarPage.vue'),
                meta: { module: 'habits' },
            },
            {
                path: 'habits/:id/stats',
                name: 'habit-stats',
                component: () => import('@/pages/habits/HabitStatsPage.vue'),
                props: true,
                meta: { module: 'habits' },
            },
            {
                path: 'boards',
                name: 'boards',
                component: () => import('@/pages/tasks/BoardsPage.vue'),
                meta: { module: 'tasks' },
            },
            {
                path: 'boards/:id',
                name: 'board',
                component: () => import('@/pages/tasks/BoardPage.vue'),
                props: true,
                meta: { module: 'tasks' },
            },
            {
                path: 'finance',
                component: () => import('@/pages/finance/FinanceLayout.vue'),
                meta: { module: 'finance' },
                children: [
                    {
                        path: '',
                        name: 'finance',
                        component: () => import('@/pages/finance/FinanceDashboard.vue'),
                    },
                    {
                        path: 'transactions',
                        name: 'finance-transactions',
                        component: () => import('@/pages/finance/TransactionsPage.vue'),
                    },
                    {
                        path: 'accounts',
                        name: 'finance-accounts',
                        component: () => import('@/pages/finance/AccountsPage.vue'),
                    },
                    {
                        path: 'categories',
                        name: 'finance-categories',
                        component: () => import('@/pages/finance/CategoriesPage.vue'),
                    },
                    {
                        path: 'taxes',
                        name: 'finance-taxes',
                        component: () => import('@/pages/finance/TaxesPage.vue'),
                    },
                    {
                        path: 'invoices',
                        name: 'finance-invoices',
                        component: () => import('@/pages/finance/InvoicesPage.vue'),
                    },
                    {
                        path: 'recurring',
                        name: 'finance-recurring',
                        component: () => import('@/pages/finance/RecurringPage.vue'),
                    },
                    {
                        path: 'budgets',
                        name: 'finance-budgets',
                        component: () => import('@/pages/finance/BudgetsPage.vue'),
                    },
                ],
            },
            {
                path: 'drive',
                name: 'drive',
                component: () => import('@/pages/storage/DrivePage.vue'),
                meta: { module: 'storage' },
            },
        ],
    },
]

const router = createRouter({
    history: createWebHistory(),
    routes,
})

router.beforeEach(async (to) => {
    const auth = useAuthStore()

    if (!auth.checked) {
        await auth.fetchUser()
    }

    if (to.meta.auth && !auth.isAuthenticated) {
        return { name: 'login' }
    }

    if (to.meta.guest && auth.isAuthenticated) {
        return { name: 'dashboard' }
    }

    if (to.meta.module && !auth.hasModule(to.meta.module)) {
        return { name: 'dashboard' }
    }
})

export default router
