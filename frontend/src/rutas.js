import { createRouter, createWebHistory } from "vue-router"
import Login from "./componentes/LoginComponent.vue"

const rutas = [
    // hay dos formas de definir las rutas, una es con el componente y la otra es con la función import
    // la función import es la que se usa para cargar los componentes de forma asíncrona, es decir, solo se cargan cuando se accede a la ruta
    // la otra forma es la que se usa para cargar los componentes de forma síncrona, es decir, se cargan al inicio de la aplicación aunque no
    //  se acceda a la ruta
    {
        path: '/',
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