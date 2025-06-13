<template>
    <nav class="navbar navbar-expand-lg shadow-sm navbar-light" style="background-color: #232a3a;">
        <div class="container d-flex justify-content-between">
            <RouterLink class="navbar-brand d-flex align-items-center" to="/inicio">
                <img src="/favicon.png" alt="Logo" style="height: 50px; margin-right: 10px;" />
                <span class="fw-bold text-white">Todo Videojuegos</span>
            </RouterLink>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navegacion"
                aria-controls="navegacion" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navegacion">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <RouterLink class="nav-link text-white" to="/videojuegos">Videojuegos</RouterLink>
                    </li>
                    <li v-if="rolToken == 'administrador'" class="nav-item">
                        <RouterLink class="nav-link text-warning" to="/gestion-usuarios">Gestión de Usuarios
                        </RouterLink>
                    </li>
                    <li v-if="rolToken == 'administrador'" class="nav-item">
                        <RouterLink class="nav-link text-warning" to="/gestion-videojuegos">Gestión de Videojuegos
                        </RouterLink>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-white" href="#" id="navbarDropdown" role="button"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user me-2"></i>
                            <span> {{ nombreCompleto }} </span>
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                            <li>
                                <RouterLink class="dropdown-item" to="/datospersonales">Datos personales</RouterLink>
                            </li>
                            <li><a class="dropdown-item btn btn-primary" @click="cerrarSesion">Cerrar Sesión</a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <RouterLink class="nav-link text-white" to="/carrito"><i class="fa-solid fa-cart-shopping"></i>
                        </RouterLink>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
</template>

<script setup lang="ts">
import { jwtDecode } from 'jwt-decode';
import { ref, onMounted } from 'vue';
import { RouterLink, useRouter } from 'vue-router';

const router = useRouter();
const rolToken = ref("");
const nombreCompleto = ref("");

onMounted(() => {
    rolToken.value = jwtDecode<any>(localStorage.getItem("token") || "").roles[0];
    nombreCompleto.value = jwtDecode<any>(localStorage.getItem("token") || "").username + " " +
        jwtDecode<any>(localStorage.getItem("token") || "").apellido;
});

const cerrarSesion = () => {
    localStorage.removeItem("token");
    router.push("/login");
};
</script>

<style scoped>
.navbar-toggler {
    border-color: #fff !important;
}

.navbar-toggler-icon {
    background-image: url("data:image/svg+xml;charset=utf8,%3Csvg viewBox='0 0 30 30' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath stroke='rgba(255,255,255,1)' stroke-width='2' stroke-linecap='round' stroke-miterlimit='10' d='M4 7h22M4 15h22M4 23h22'/%3E%3C/svg%3E") !important;
}
</style>