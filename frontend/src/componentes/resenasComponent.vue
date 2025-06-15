<template>
    <div class="container my-4">
        <h3 class="mb-3">Reseñas</h3>
        <div v-if="reseñas.length === 0" class="alert alert-info">No hay reseñas para este producto.</div>
        <div v-else>
            <div v-for="resena in reseñas" :key="resena.id" class="card mb-3">
                <div class="card-body">
                    <h5 class="card-title mb-1">{{ resena.usuario }}</h5>
                    <p class="card-text mb-1">{{ resena.comentario }}</p>
                    <small class="text-muted">Puntuación: {{ resena.puntuacion }}/5</small>
                </div>
            </div>
        </div>
        <div class="card mt-4">
            <div class="card-body">
                <h5 class="card-title">Deja tu reseña</h5>
                <div v-if="mensajeError" class="alert alert-danger">{{ mensajeError }}</div>
                <textarea v-model="nuevoComentario" class="form-control mb-2" rows="2" placeholder="Escribe tu reseña..."></textarea>
                <label>Puntuación:
                    <select v-model="nuevaPuntuacion" class="form-select w-auto d-inline-block ms-2">
                        <option v-for="n in 5" :key="n" :value="n">{{ n }}</option>
                    </select>
                </label>
                <button class="btn btn-primary mt-2 ms-2" @click="enviarResena">Enviar</button>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import urlBackend from '@/rutaApi';

const props = defineProps<{ productoId: number }>();
const reseñas = ref<any[]>([]);
const nuevoComentario = ref('');
const nuevaPuntuacion = ref(5);
const mensajeError = ref('');

const fetchResenas = async () => {
    try {
        const token = localStorage.getItem('token');
        const response = await fetch(urlBackend + '/api/resenas/listar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ token, productoId: props.productoId })
        });
        const data = await response.json();
        reseñas.value = data || [];
    } catch (e) {
        reseñas.value = [];
    }
};

const enviarResena = async () => {
    mensajeError.value = '';
    if (!nuevoComentario.value.trim() || !nuevaPuntuacion.value) {
        mensajeError.value = 'Debes introducir una puntuación.';
        return;
    }
    // Aquí deberías hacer la petición al backend para guardar la reseña
    reseñas.value.push({
        id: Date.now(),
        usuario: 'Tú',
        comentario: nuevoComentario.value,
        puntuacion: nuevaPuntuacion.value
    });
    nuevoComentario.value = '';
    nuevaPuntuacion.value = 5;
};

onMounted(() => {
    fetchResenas();
});
</script>
