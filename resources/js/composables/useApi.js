import axios from 'axios'
import router from '@/router'

const api = axios.create({
    baseURL: '/api',
    withCredentials: true,
    headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
    },
})

api.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401) {
            router.push({ name: 'login' })
        }
        return Promise.reject(error)
    }
)

export default api
