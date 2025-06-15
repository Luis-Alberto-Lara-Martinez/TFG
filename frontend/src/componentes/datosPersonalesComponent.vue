<template>
    <MenuComponent />
    <div class="container my-5 text-white">
        <h2 class="mb-4 text-center">Modificar mis datos</h2>
        <div v-if="cargando" class="text-center my-5">
            <span class="spinner-border"></span>
        </div>
        <form v-else @submit.prevent="guardarCambios" class="bg-white p-4 rounded text-dark w-100 w-md-50 mx-auto">
            <div class="mb-3">
                <label for="nombre" class="form-label">Nombre</label>
                <input v-model="usuario.nombre" type="text" id="nombre" class="form-control" required />
            </div>
            <div class="mb-3">
                <label for="apellido" class="form-label">Apellido</label>
                <input v-model="usuario.apellido" type="text" id="apellido" class="form-control" required />
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input v-model="usuario.email" type="email" id="email" class="form-control" required />
            </div>
            <div class="mb-3">
                <label for="telefono" class="form-label">Teléfono</label>
                <input v-model="usuario.telefono" type="text" id="telefono" class="form-control" required />
            </div>
            <div class="mb-3">
                <label for="direccion" class="form-label">Dirección</label>
                <input v-model="usuario.direccion" type="text" id="direccion" class="form-control" required />
            </div>
            <div v-if="error" class="alert alert-danger">{{ error }}</div>
            <div v-if="exito" class="alert alert-success">Datos actualizados correctamente.</div>
            <button type="submit" class="btn btn-success w-100" :disabled="cargando">
                <span v-if="cargando" class="spinner-border spinner-border-sm me-2"></span>
                <span v-else>Guardar cambios</span>
            </button>
        </form>
        <ScrollBotonComponent />
    </div>
    <PiePaginaComponent />
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import MenuComponent from './menuComponent.vue';
import PiePaginaComponent from './piePaginaComponent.vue';
import urlBackend from '@/rutaApi';
import { useRouter } from 'vue-router';
import ScrollBotonComponent from './scrollBotonComponent.vue';

const usuario = ref<any>({});
const cargando = ref(true);
const error = ref('');
const exito = ref(false);
const router = useRouter();

const fetchUsuario = async () => {
    cargando.value = true;
    error.value = '';
    try {
        const token = localStorage.getItem('token');
        const response = await fetch(urlBackend + '/api/usuarios/datosPersonales', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ token })
        });
        const data = await response.json();
        if (data.error) {
            error.value = data.error;
        } else {
            usuario.value = data;
        }
    } catch (e) {
        error.value = 'Error al cargar los datos';
    } finally {
        cargando.value = false;
    }
};

const guardarCambios = async () => {
    cargando.value = true;
    error.value = '';
    exito.value = false;
    try {
        const token = localStorage.getItem('token');
        const { createdAt, createdBy, modifiedAt, modifiedBy, rol, deleted, ...datosEditables } = usuario.value;
        const response = await fetch(urlBackend + '/api/usuarios/editarUsuario', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ ...datosEditables, token })
        });
        const data = await response.json();
        if (data.error) {
            error.value = data.error;
        } else {
            exito.value = true;
            await fetchUsuario();
        }
    } catch (e) {
        error.value = 'Error al guardar los cambios';
    } finally {
        cargando.value = false;
    }
};

onMounted(fetchUsuario);
</script>

<style scoped>
form {
    max-width: 500px;
}
</style>