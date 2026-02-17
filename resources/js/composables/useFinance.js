import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import { computed, unref } from 'vue'
import api from './useApi'

function serializeFilters(filters) {
    const raw = filters ? unref(filters) : null

    if (!raw) {
        return {}
    }

    const params = {}

    if (raw.startDate) {
        params.start_date = raw.startDate
    }

    if (raw.endDate) {
        params.end_date = raw.endDate
    }

    if (raw.type) {
        params.type = raw.type
    }

    if (raw.categoryId) {
        params.category_id = raw.categoryId
    }

    if (raw.accountId) {
        params.account_id = raw.accountId
    }

    return params
}

function filtersKey(filters) {
    return JSON.stringify(serializeFilters(filters))
}

export function useFinanceSummary(filters) {
    return useQuery({
        queryKey: computed(() => ['finance', 'summary', filtersKey(filters)]),
        queryFn: () => api
            .get('/finance/transactions/summary', { params: serializeFilters(filters) })
            .then(r => r.data),
    })
}

export function useFinanceTransactions(filters) {
    return useQuery({
        queryKey: computed(() => ['finance', 'transactions', filtersKey(filters)]),
        queryFn: () => api
            .get('/finance/transactions', { params: serializeFilters(filters) })
            .then(r => r.data),
    })
}

export function useFinanceCategories(type) {
    return useQuery({
        queryKey: computed(() => ['finance', 'categories', unref(type) ?? 'all']),
        queryFn: () => {
            const params = {}
            const value = unref(type)

            if (value && value !== 'all') {
                params.type = value
            }

            return api.get('/finance/categories', { params }).then(r => r.data)
        },
    })
}

function invalidateFinanceData(qc) {
    qc.invalidateQueries({ queryKey: ['finance', 'summary'] })
    qc.invalidateQueries({ queryKey: ['finance', 'transactions'] })
    qc.invalidateQueries({ queryKey: ['finance', 'accounts'] })
}

export function useCreateTransaction() {
    const qc = useQueryClient()

    return useMutation({
        mutationFn: (data) => api.post('/finance/transactions', data).then(r => r.data),
        onSuccess: () => {
            invalidateFinanceData(qc)
        },
    })
}

export function useUpdateTransaction() {
    const qc = useQueryClient()

    return useMutation({
        mutationFn: ({ id, data }) => api.put(`/finance/transactions/${id}`, data).then(r => r.data),
        onSuccess: () => {
            invalidateFinanceData(qc)
        },
    })
}

export function useDeleteTransaction() {
    const qc = useQueryClient()

    return useMutation({
        mutationFn: (id) => api.delete(`/finance/transactions/${id}`).then(r => r.data),
        onSuccess: () => {
            invalidateFinanceData(qc)
        },
    })
}

export function useCreateCategory() {
    const qc = useQueryClient()

    return useMutation({
        mutationFn: (data) => api.post('/finance/categories', data).then(r => r.data),
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['finance', 'categories'] })
        },
    })
}

export function useUpdateCategory() {
    const qc = useQueryClient()

    return useMutation({
        mutationFn: ({ id, data }) => api.put(`/finance/categories/${id}`, data).then(r => r.data),
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['finance', 'categories'] })
        },
    })
}

export function useDeleteCategory() {
    const qc = useQueryClient()

    return useMutation({
        mutationFn: (id) => api.delete(`/finance/categories/${id}`).then(r => r.data),
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['finance', 'categories'] })
            invalidateFinanceData(qc)
        },
    })
}

export function useAutoCategorize() {
    return useMutation({
        mutationFn: (transactionIds) =>
            api.post('/finance/transactions/auto-categorize', {
                transaction_ids: transactionIds ?? [],
            }).then(r => r.data),
    })
}

export function useApplyCategories() {
    const qc = useQueryClient()

    return useMutation({
        mutationFn: (assignments) =>
            api.post('/finance/transactions/apply-categories', { assignments }).then(r => r.data),
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['finance'] })
        },
    })
}
