<template>
  <MenuComponent />
  <div class="container my-5 text-white">
    <h3 class="mb-3">Reseñas de Videojuegos</h3>

    <div class="mb-4">
      <select class="form-select" v-model="productoId" @change="onProductoSeleccionado">
        <option value="0" disabled>Selecciona un videojuego</option>
        <option v-for="producto in productos" :key="producto.id" :value="producto.id">
          {{ producto.nombre }} - {{ producto.plataforma }}
        </option>
      </select>
    </div>

    <div class="card mt-4 mb-3" v-if="productoId">
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

        <button class="btn btn-primary mt-2" @click="enviarResena" :disabled="isLoading">
          <span v-if="isLoading" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
          <span v-if="isLoading">Enviando...</span>
          <span v-else>Enviar</span>
        </button>
      </div>
    </div>

    <div v-if="productoId != 0">
      <div v-if="paginatedResenas.length === 0 && reseñas.length === 0" class="alert alert-info">
        No hay reseñas para este videojuego.
      </div>
      <div v-else id="reviews-section">
        <div v-for="resena in paginatedResenas" :key="resena.id" class="card mb-3">
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

        <nav v-if="totalPages > 1">
          <ul class="pagination justify-content-center">
            <li class="page-item me-2" :class="{ disabled: currentPage === 1 }">
              <button class="btn btn-primary" @click="prevPage" :disabled="currentPage === 1">Anterior</button>
            </li>
            <li class="page-item mx-2 d-flex align-items-center">
              Página {{ currentPage }}
            </li>
            <li class="page-item ms-2" :class="{ disabled: currentPage === totalPages }">
              <button class="btn btn-primary" @click="nextPage"
                :disabled="currentPage === totalPages">Siguiente</button>
            </li>
          </ul>
        </nav>
      </div>
    </div>
    <ScrollBotonComponent />
  </div>
  <PiePaginaComponent />
</template>

<script setup lang="ts">
import { ref, onMounted, computed, nextTick } from 'vue'; // Import nextTick
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
const isLoading = ref(false);

const currentPage = ref(1);
const reviewsPerPage = 5;

const paginatedResenas = computed(() => {
  const start = (currentPage.value - 1) * reviewsPerPage;
  const end = start + reviewsPerPage;
  return reseñas.value.slice(start, end);
});

const totalPages = computed(() => {
  return Math.ceil(reseñas.value.length / reviewsPerPage);
});

const scrollToReviews = async () => {
  await nextTick();
  const reviewsSection = document.getElementById('reviews-section');
  if (reviewsSection) {
    reviewsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }
};

const prevPage = () => {
  if (currentPage.value > 1) {
    currentPage.value--;
    scrollToReviews();
  }
};

const nextPage = () => {
  if (currentPage.value < totalPages.value) {
    currentPage.value++;
    scrollToReviews();
  }
};

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

const onProductoSeleccionado = () => {
  currentPage.value = 1;
  if (productoId.value) {
    fetchResenas(productoId.value);
  } else {
    reseñas.value = [];
  }
};

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
  } catch (e) {
    reseñas.value = [];
  }
};

const enviarResena = async () => {
  mensajeError.value = '';
  if (!nuevaPuntuacion.value || !productoId.value) {
    mensajeError.value = 'Debes seleccionar un videojuego y una puntuación.';
    return;
  }

  isLoading.value = true;
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
    if (response.ok) {
      fetchResenas(productoId.value);
      nuevoComentario.value = '';
      nuevaPuntuacion.value = 5;
      currentPage.value = 1;
      scrollToReviews();
    } else {
      mensajeError.value = data.message || 'Error al enviar la reseña.';
    }
  } catch (e) {
    mensajeError.value = 'Error al enviar la reseña.';
  } finally {
    isLoading.value = false;
  }
};

onMounted(() => {
  cargarProductos();
});
</script>

<style scoped></style>