import { createRouter, createWebHistory } from "vue-router"
import Login from "./componentes/LoginComponent.vue"

const rutas = [
    {
        path: '/',
        redirect: '/login'
    },
    {
        path: '/:pathMatch(.*)*',
        redirect: '/login'
    },
    {
        path: '/login',
        component: Login
    },
    {
        path: '/inicio',
        component: () => import("./componentes/InicioComponent.vue")
    }
]

const router = createRouter({
    history: createWebHistory(process.env.BASE_URL),
    routes: rutas
})

export default router