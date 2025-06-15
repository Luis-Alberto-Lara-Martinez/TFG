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
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="usuario in usuarios" :key="usuario.id">
                                <td>{{ usuario.id }}</td>
                                <td v-if="editandoId !== usuario.id">{{ usuario.nombre }}</td>
                                <td v-else><input v-model="editUsuario.nombre" class="form-control" /></td>
                                <td v-if="editandoId !== usuario.id">{{ usuario.apellido }}</td>
                                <td v-else><input v-model="editUsuario.apellido" class="form-control" /></td>
                                <td v-if="editandoId !== usuario.id">{{ usuario.email }}</td>
                                <td v-else><input v-model="editUsuario.email" class="form-control" /></td>
                                <td v-if="editandoId !== usuario.id">{{ usuario.telefono }}</td>
                                <td v-else><input v-model="editUsuario.telefono" class="form-control" /></td>
                                <td v-if="editandoId !== usuario.id">{{ usuario.direccion }}</td>
                                <td v-else><input v-model="editUsuario.direccion" class="form-control" /></td>
                                <td v-if="editandoId !== usuario.id">{{ usuario.rol }}</td>
                                <td v-else>
                                    <select v-model="editUsuario.rol" class="form-control">
                                        <option v-for="rol in roles" :key="rol.id" :value="rol.nombre">{{ rol.nombre }}
                                        </option>
                                    </select>
                                </td>
                                <td>{{ usuario.createdAt }} {{ usuario.createdBy }}</td>
                                <td>{{ usuario.modifiedAt }} {{ usuario.modifiedBy }}</td>
                                <td v-if="editandoId !== usuario.id">
                                    <span :class="usuario.deleted ? 'badge bg-danger p-2' : 'badge bg-success p-2'">
                                        {{ usuario.deleted ? 'Inactivo' : 'Activo' }}
                                    </span>
                                </td>
                                <td v-else>
                                    <select v-model="editUsuario.deleted" class="form-control">
                                        <option :value="false">Activo</option>
                                        <option :value="true">Inactivo</option>
                                    </select>
                                </td>
                                <td>
                                    <button v-if="editandoId !== usuario.id" class="btn btn-warning btn-sm"
                                        @click="empezarEdicion(usuario)"><i class="fa fa-edit"></i></button>
                                    <button v-else class="btn btn-success btn-sm me-1" @click="guardarEdicion"><i
                                            class="fa fa-save"></i></button>
                                    <button v-if="editandoId === usuario.id" class="btn btn-secondary btn-sm"
                                        @click="cancelarEdicion"><i class="fa fa-times"></i></button>
                                </td>
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
import { useRouter } from 'vue-router';

const usuarios = ref<any[]>([]);
const cargando = ref(true);
const error = ref('');
const router = useRouter();

const editandoId = ref<number | null>(null);
const editUsuario = ref<any>({});
const roles = ref<any[]>([]);

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
        if (response.status === 401) {
            localStorage.removeItem('token');
            router.push('/login');
            return;
        }
        const data = await response.json();
        if (Array.isArray(data)) {
            usuarios.value = data;
            console.log('Usuarios obtenidos:', usuarios.value);
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

const fetchRoles = async () => {
    try {
        const token = localStorage.getItem('token');
        const response = await fetch(urlBackend + '/api/usuarios/listarRoles', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ token })
        });
        const data = await response.json();
        if (Array.isArray(data)) {
            roles.value = data;
        }
    } catch (e) {
        // No error visible, solo para edición
    }
};

function empezarEdicion(usuario: any) {
    editandoId.value = usuario.id;
    editUsuario.value = { ...usuario };
}

function cancelarEdicion() {
    editandoId.value = null;
    editUsuario.value = {};
}

async function guardarEdicion() {
    cargando.value = true;
    error.value = '';
    try {
        const token = localStorage.getItem('token');
        // Excluir campos que no se deben enviar
        const {
            createdAt, createdBy, modifiedAt, modifiedBy, ...usuarioEditado
        } = editUsuario.value;
        const response = await fetch(urlBackend + '/api/usuarios/editarUsuario', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ ...usuarioEditado, token })
        });
        const data = await response.json();
        if (data.error) {
            error.value = data.error;
        } else {
            await fetchUsuarios();
            cancelarEdicion();
        }
    } catch (e) {
        error.value = 'Error al guardar los cambios';
    } finally {
        cargando.value = false;
    }
}

onMounted(() => {
    fetchUsuarios();
    fetchRoles();
});
</script>

<style scoped>
th,
td {
    vertical-align: middle;
}
</style>