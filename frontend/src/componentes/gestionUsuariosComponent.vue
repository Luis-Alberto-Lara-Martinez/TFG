<template>
    <MenuComponent />
    <div class="container my-5 text-white">
        <h2 class="mb-4 text-center">Usuarios registrados</h2>
        <div v-if="cargando" class="text-center my-5">
            <span class="spinner-border"></span>
        </div>
        <div v-else>
            <div v-if="error" class="alert alert-danger">{{ error }}</div>
            <div v-else>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-striped text-center">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Apellido</th>
                                <th>Email</th>
                                <th>Teléfono</th>
                                <th>Dirección</th>
                                <th>Rol</th>
                                <th>Creado</th>
                                <th>Modificado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="usuario in usuarios" :key="usuario.id">
                                <td>{{ usuario.id }}</td>
                                <td>{{ usuario.nombre }}</td>
                                <td>{{ usuario.apellido }}</td>
                                <td>{{ usuario.email }}</td>
                                <td>{{ usuario.telefono }}</td>
                                <td>{{ usuario.direccion }}</td>
                                <td>{{ usuario.rol }}</td>
                                <td>{{ usuario.createdAt }} {{ usuario.createdBy }}</td>
                                <td>{{ usuario.modifiedAt }} {{ usuario.createdBy }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <ScrollBotonComponent />
    </div>
    <PiePaginaComponent />
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import MenuComponent from './menuComponent.vue';
import PiePaginaComponent from './piePaginaComponent.vue';
import ScrollBotonComponent from './scrollBotonComponent.vue';
import urlBackend from '@/rutaApi';

const usuarios = ref<any[]>([]);
const cargando = ref(true);
const error = ref('');

const fetchUsuarios = async () => {
    cargando.value = true;
    error.value = '';
    try {
        const token = localStorage.getItem('token');
        const response = await fetch(urlBackend + '/api/usuarios/listarUsuarios', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ token })
        });
        const data = await response.json();
        if (Array.isArray(data)) {
            usuarios.value = data;
        } else if (data.error) {
            error.value = data.error;
        } else {
            error.value = 'Error desconocido al obtener usuarios';
        }
    } catch (e) {
        error.value = 'Error de red o servidor';
    } finally {
        cargando.value = false;
    }
};

onMounted(fetchUsuarios);
</script>

<style scoped>
th,
td {
    vertical-align: middle;
}
</style>