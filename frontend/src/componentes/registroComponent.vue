<template>
    <div class="container">
        <video class="registro-video-bg" autoplay muted loop playsinline>
            <source src="/portada2.mp4" type="video/mp4" />
        </video>
        <div class="d-flex justify-content-center align-items-center vh-100 registro-overlay my-5">
            <div class="login p-5 rounded d-flex flex-column text-white">
                <h2 class="text-center mb-4">
                    <img src="/favicon.png" alt="Logo" style="width: 100px;">
                    Todo Videojuegos
                </h2>
                <form @submit.prevent="registrarUsuario">
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre <span class="text-danger">*</span></label>
                        <input v-model="nombre" type="text" id="nombre" class="form-control" required />
                    </div>
                    <div class="mb-3">
                        <label for="apellido" class="form-label">Apellido <span class="text-danger">*</span></label>
                        <input v-model="apellido" type="text" id="apellido" class="form-control" required />
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                        <input v-model="email" type="email" id="email" class="form-control" required
                            placeholder="example@gmail.com" />
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Contraseña <span class="text-danger">*</span></label>
                        <input v-model="password" type="password" id="password" class="form-control" required />
                    </div>
                    <div class="mb-3">
                        <label for="telefono" class="form-label">Teléfono <span class="text-danger">*</span></label>
                        <input v-model="telefono" type="text" id="telefono" class="form-control" required
                            pattern="^\d{9}$" placeholder="9 dígitos" />
                    </div>
                    <div class="mb-3">
                        <label for="direccion" class="form-label">Dirección <span class="text-danger">*</span></label>
                        <input v-model="direccion" type="text" id="direccion" class="form-control"
                            placeholder="Debe empezar por Calle/Plaza/Avenida"
                            pattern="^(Calle|Plaza|Avenida|calle|plaza|avenida)[\s0-9a-záéíóúñA-ZÁÉÍÓÚÑ]+$" required />
                    </div>
                    <div v-if="error" class="mb-3">
                        <div class="alert alert-danger">{{ error }}</div>
                    </div>
                    <div v-if="exito" class="mb-3">
                        <div class="alert alert-success">Usuario registrado correctamente. Ya puedes iniciar sesión.
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100" :disabled="cargando">
                        <span v-if="cargando" class="spinner-border spinner-border-sm me-2"></span>
                        <span v-else>Registrarse</span>
                    </button>
                </form>
                <div class="mt-3 text-center">
                    <span>¿Ya tienes cuenta?</span>
                    <button class="btn btn-link p-0 ms-1" @click="router.push('/login')">Inicia sesión</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import urlBackend from '@/rutaApi';
import { ref } from 'vue';
import { useRouter } from 'vue-router';

const nombre = ref("");
const apellido = ref("");
const email = ref("");
const password = ref("");
const telefono = ref("");
const direccion = ref("");
const cargando = ref(false);
const error = ref("");
const exito = ref(false);
const router = useRouter();

const registrarUsuario = async () => {
    cargando.value = true;
    error.value = "";
    exito.value = false;
    try {
        const response = await fetch(urlBackend + "/api/usuarios/registro", {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                nombre: nombre.value,
                apellido: apellido.value,
                email: email.value,
                password: password.value,
                telefono: telefono.value,
                direccion: direccion.value
            })
        });

        const data = await response.json();
        if (data.error) {
            error.value = data.error;
        } else {
            localStorage.setItem("token", data.token);
            router.push('/inicio').then(() => {
                window.scrollTo(0, 0);
            });
        }
    } catch (e) {
        error.value = 'Error de red o servidor';
    } finally {
        cargando.value = false;
    }
};
</script>

<style scoped>
.registro-video-bg {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    object-fit: cover;
    z-index: -1;
}

.registro-overlay {
    position: relative;
    z-index: 1;
}

.login {
    background-color: rgba(0, 0, 0, 0.7) !important;
}
</style>