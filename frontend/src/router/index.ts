import { createRouter, createWebHistory } from 'vue-router'
import LoginComponent from '@/componentes/loginComponent.vue'
import InicioComponent from '@/componentes/inicioComponent.vue'
import RegistroComponent from '@/componentes/registroComponent.vue';
import VideojuegosComponent from '@/componentes/videojuegosComponent.vue';
import HistorialComprasComponent from '@/componentes/historialComprasComponent.vue';
import GestionUsuariosComponent from '@/componentes/gestionUsuariosComponent.vue';
import GestionVideojuegosPropiosComponent from '@/componentes/gestionVideojuegosPropiosComponent.vue';
import GestionVideojuegosNuevosComponent from '@/componentes/gestionVideojuegosNuevosComponent.vue';

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    { path: '/login', name: 'login', component: LoginComponent },
    { path: '/registro', name: 'registro', component: RegistroComponent },
    { path: '/inicio', name: 'inicio', component: InicioComponent },
    { path: '/videojuegos', name: 'videojuegos', component: VideojuegosComponent },
    { path: '/historial-compras', name: 'historial-compras', component: HistorialComprasComponent },
    { path: '/gestion-usuarios', name: 'gestion-usuarios', component: GestionUsuariosComponent },
    { path: '/gestion-videojuegos', name: 'gestion-videojuegos', component: GestionVideojuegosPropiosComponent },
    { path: '/gestion-videojuegos-nuevo', name: 'gestion-videojuegos-nuevo', component: GestionVideojuegosNuevosComponent },

    { path: '/', redirect: '/inicio' },
    { path: '/:pathMatch(.*)*', redirect: '/inicio' },
  ],
})

router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('token');
  if (to.name !== 'login' && to.name !== 'registro' && !token) {
    next({ name: 'login' });
  } else if ((to.name === 'login' || to.name === 'registro') && token) {
    next({ name: 'inicio' });
  } else {
    next();
  }
});

export default router
