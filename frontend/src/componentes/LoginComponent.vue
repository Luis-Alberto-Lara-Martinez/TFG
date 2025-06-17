<template>
    <div class="login-bg-container">
        <video class="login-video-bg" autoplay muted loop playsinline>
            <source src="/portada2.mp4" type="video/mp4" />
        </video>
        <div class="d-flex justify-content-center align-items-center vh-100 login-overlay">
            <div class="login text-white p-5 rounded d-flex flex-column">
                <h2 class="text-center mb-4">
                    <img src="/favicon.png" alt="Logo" style="height: 100px;">
                    Todo Videojuegos
                </h2>
                <form @submit.prevent="iniciarSesion">
                    <div class="mb-4">
                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                        <input v-model="email" type="email" id="email" name="email" class="form-control" required>
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label">Contraseña <span class="text-danger">*</span></label>
                        <input v-model="password" type="password" id="password" name="password" class="form-control"
                            required>
                    </div>

                    <div v-if="error != ''" class="mb-4">
                        <div class="alert alert-danger" role="alert">{{ error }}</div>
                    </div>

                    <button v-if="cargando" type="submit" class="btn btn-primary w-100" disabled>
                        <span class="spinner-border spinner-border-sm text-white me-2" role="status"
                            aria-hidden="true"></span>
                        <span>Cargando</span>
                    </button>
                    <button v-else type="submit" class="btn btn-primary w-100">
                        Iniciar Sesión
                    </button>
                </form>
                <div class="mt-3 text-center">
                    <span>¿No tienes cuenta?</span>
                    <button class="btn btn-link p-0 ms-1" @click="router.push('/registro')">Regístrate</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import urlBackend from '@/rutaApi';
import { ref } from 'vue';
import { useRouter } from 'vue-router';

const cargando = ref(false);
const error = ref("");
const email = ref("");
const password = ref("");
const router = useRouter();

const iniciarSesion = async () => {
    cargando.value = true;
    error.value = "";
    try {
        const response = await fetch(urlBackend + "/api/usuarios/login", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                email: email.value,
                password: password.value
            })
        });
        const respuesta = await response.json();
        cargando.value = false;
        if (!response.ok || respuesta.error) {
            error.value = respuesta.error;
        } else {
            localStorage.setItem("token", respuesta.token);
            router.push('/inicio');
        }
    } catch (err) {
        cargando.value = false;
        error.value = "Error al iniciar sesión. Por favor, inténtalo de nuevo más tarde";
    }
};
</script>

<style scoped>
.login-video-bg {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    object-fit: cover;
    z-index: -1;
}

.login-overlay {
    position: relative;
    z-index: 1;
}

.login {
    background-color: rgba(0, 0, 0, 0.7);
}
</style>