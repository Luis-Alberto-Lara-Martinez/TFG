<template>
  <MenuComponent />
  <div class="container my-5 text-white">
    <h3 class="mb-3">Reseñas de Videojuegos</h3>

    <!-- Selector de productos -->
    <div class="mb-4">
      <select class="form-select" v-model="productoId" @change="onProductoSeleccionado">
        <option value="0" disabled>Selecciona un videojuego</option>
        <option v-for="producto in productos" :key="producto.id" :value="producto.id">
          {{ producto.nombre }} - {{ producto.plataforma }}
        </option>
      </select>
    </div>

    <!-- Reseñas -->
    <div v-if="productoId != 0">
      <div v-if="reseñas.length === 0" class="alert alert-info">
        No hay reseñas para este videojuego.
      </div>
      <div v-else>
        <div v-for="resena in reseñas" :key="resena.id" class="card mb-3">
          <div class="card-body">
            <h5 class="card-title mb-1">{{ resena.usuario }}</h5>
            <span>{{ resena.fecha }}</span>
            <p class="card-text mb-1">{{ resena.comentario }}</p>
            <div class="text-warning">
              <span v-for="n in 5" :key="n">
                <i class="fa-star" :class="n <= resena.nota ? 'fas' : 'far'"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Añadir Reseña -->
    <div class="card mt-4" v-if="productoId">
      <div class="card-body">
        <h5 class="card-title">Deja tu reseña</h5>
        <div v-if="mensajeError" class="alert alert-danger">
          {{ mensajeError }}
        </div>
        <textarea v-model="nuevoComentario" class="form-control mb-2" rows="2"
          placeholder="Escribe tu reseña..."></textarea>

        <div class="mb-2">
          <label>Puntuación:</label>
          <div class="text-warning">
            <i v-for="n in 5" :key="n" class="fa-star me-1" :class="n <= nuevaPuntuacion ? 'fas' : 'far'"
              style="cursor: pointer" @click="nuevaPuntuacion = n"></i>
          </div>
        </div>

        <button class="btn btn-primary mt-2" @click="enviarResena">
          Enviar
        </button>
      </div>
    </div>
    <ScrollBotonComponent />
  </div>
  <PiePaginaComponent />
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import urlBackend from '@/rutaApi';
import MenuComponent from './menuComponent.vue';
import ScrollBotonComponent from './scrollBotonComponent.vue';
import PiePaginaComponent from './piePaginaComponent.vue';

const reseñas = ref<any[]>([]);
const productos = ref<any[]>([]);
const productoId = ref<number>(0);
const nuevoComentario = ref('');
const nuevaPuntuacion = ref(5);
const mensajeError = ref('');

// Cargar todos los productos al iniciar
const cargarProductos = async () => {
  try {
    const token = localStorage.getItem('token');
    const response = await fetch(urlBackend + '/api/videojuegos/listarVideojuegos', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ token })
    });
    const data = await response.json();
    productos.value = data || [];
  } catch (e) {
    productos.value = [];
  }
};

// Cuando el usuario selecciona un producto
const onProductoSeleccionado = () => {
  if (productoId.value) {
    fetchResenas(productoId.value);
  } else {
    reseñas.value = [];
  }
};

// Obtener reseñas por ID de producto
const fetchResenas = async (id: number) => {
  try {
    const token = localStorage.getItem('token');
    const response = await fetch(urlBackend + '/api/videojuegos/listarReviews', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ token, productoId: id })
    });
    const data = await response.json();
    reseñas.value = data || [];
    console.log(data);
  } catch (e) {
    reseñas.value = [];
  }
};

// Enviar nueva reseña
const enviarResena = async () => {
  mensajeError.value = '';
  if (!nuevaPuntuacion.value || !productoId.value) {
    mensajeError.value = 'Debes seleccionar un videojuego y una puntuación.';
    return;
  }

  try {
    const token = localStorage.getItem('token');
    const response = await fetch(urlBackend + '/api/videojuegos/crearReview', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        token,
        videojuego_id: productoId.value,
        comentario: nuevoComentario.value,
        nota: nuevaPuntuacion.value
      })
    });
    const data = await response.json();
    fetchResenas(productoId.value);
    nuevoComentario.value = '';
    nuevaPuntuacion.value = 5;
  } catch (e) {
    mensajeError.value = 'Error al enviar la reseña.';
  }
};

// Cargar productos al montar
onMounted(() => {
  cargarProductos();
});
</script>

<style>
@import 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css';
</style>
