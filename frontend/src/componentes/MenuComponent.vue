<template>
    <nav class="navbar navbar-expand-lg shadow-sm navbar-light" style="background-color: #232a3a;">
        <div class="container d-flex justify-content-between">
            <RouterLink class="navbar-brand d-flex align-items-center" to="/inicio">
                <img src="/favicon.png" alt="Logo" style="height: 50px; margin-right: 10px;" />
                <span class="fw-bold text-white">Todo Videojuegos</span>
            </RouterLink>
            <button class="boton-oculto navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#navegacion" aria-controls="navegacion" aria-expanded="false"
                aria-label="Toggle navigation">
                <span class="boton-oculto navbar-toggler-icon"></span>
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
                    <li v-if="rolToken == 'administrador'" class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-warning" href="#" id="navbarDropdown" role="button"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            Gestión de Videojuegos
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                            <li>
                                <RouterLink class="dropdown-item" to="/gestion-videojuegos">Nuestros Videojuegos
                                </RouterLink>
                            </li>
                            <li>
                                <RouterLink class="dropdown-item" to="/gestion-videojuegos-nuevo">Añadir Videojuego
                                </RouterLink>
                            </li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-white" href="#" id="navbarDropdown" role="button"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user me-2"></i>
                            <span> {{ nombreCompleto }} </span>
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                            <li>
                                <RouterLink class="dropdown-item" to="/datos-personales">Datos personales</RouterLink>
                            </li>
                            <li>
                                <RouterLink class="dropdown-item" to="/historial-compras">Historial de compras</RouterLink>
                            </li>
                            <li>
                                <a class="dropdown-item btn" data-bs-toggle="modal"
                                    data-bs-target="#modalCambiarContrasena">Cambiar Contraseña</a>
                            </li>
                            <li><a class="dropdown-item btn" @click="cerrarSesion">Cerrar Sesión</a></li>
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

    <!-- Modal Cambiar Contraseña -->
    <div class="modal fade" id="modalCambiarContrasena" tabindex="-1" aria-labelledby="modalCambiarContrasenaLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalCambiarContrasenaLabel">Cambiar Contraseña</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <form @submit.prevent="cambiarContrasena">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="actual" class="form-label">Contraseña actual</label>
                            <input v-model="formContrasena.actual" type="password" id="actual" class="form-control"
                                required />
                        </div>
                        <div class="mb-3">
                            <label for="nueva" class="form-label">Nueva contraseña</label>
                            <input v-model="formContrasena.nueva" type="password" id="nueva" class="form-control"
                                required />
                        </div>
                        <div class="mb-3">
                            <label for="confirmar" class="form-label">Confirmar nueva contraseña</label>
                            <input v-model="formContrasena.confirmar" type="password" id="confirmar"
                                class="form-control" required />
                        </div>
                        <div v-if="errorContrasena" class="alert alert-danger">{{ errorContrasena }}</div>
                        <div v-if="exitoContrasena" class="alert alert-success">Contraseña cambiada correctamente.</div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary" :disabled="cargandoContrasena">
                            <span v-if="cargandoContrasena" class="spinner-border spinner-border-sm me-2" role="status"
                                aria-hidden="true"></span>
                            Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import urlBackend from '@/rutaApi';
import { jwtDecode } from 'jwt-decode';
import { ref, onMounted } from 'vue';
import { RouterLink, useRouter } from 'vue-router';

const router = useRouter();
const rolToken = ref("");
const nombreCompleto = ref("");

onMounted(() => {
    rolToken.value = jwtDecode<any>(localStorage.getItem("token") || "").roles[0];
    nombreCompleto.value = jwtDecode<any>(localStorage.getItem("token") || "Usuario").username + " " +
        jwtDecode<any>(localStorage.getItem("token") || "").apellido;
});

const cerrarSesion = () => {
    localStorage.removeItem("token");
    router.push("/login");
};

const formContrasena = ref({
    actual: "",
    nueva: "",
    confirmar: ""
});

const errorContrasena = ref("");
const exitoContrasena = ref(false);
const cargandoContrasena = ref(false);

const cambiarContrasena = async () => {
    errorContrasena.value = '';
    exitoContrasena.value = false;
    cargandoContrasena.value = true;
    if (formContrasena.value.nueva !== formContrasena.value.confirmar) {
        errorContrasena.value = 'La nueva contraseña y la confirmación no coinciden.';
        cargandoContrasena.value = false;
        return;
    }
    try {
        const token = localStorage.getItem('token');
        const response = await fetch(urlBackend + '/api/usuarios/cambiarContrasena', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                token,
                actual: formContrasena.value.actual,
                nueva: formContrasena.value.nueva
            })
        });
        const data = await response.json();
        if (data.error) {
            errorContrasena.value = data.error;
        } else {
            exitoContrasena.value = true;
            formContrasena.value = { actual: '', nueva: '', confirmar: '' };
        }
    } catch (e) {
        errorContrasena.value = 'Error al cambiar la contraseña.';
    } finally {
        cargandoContrasena.value = false;
    }
};
</script>

<style>
.boton-oculto {
    background-color: white !important;
}
</style>