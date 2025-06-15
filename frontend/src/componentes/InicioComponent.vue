<template>
    <MenuComponent />
    <div class="position-relative w-100 bg-dark" style="height: 100vh; overflow: hidden;">
        <video src="/portada.mp4" autoplay muted loop playsinline
            class="vw-100 vh-100 position-absolute top-0 start-0 video-bg" />
        <div
            class="position-absolute top-0 start-0 w-100 h-100 d-flex flex-column justify-content-center align-items-center overlay-bg">
            <h1 class="display-4 text-white fw-bold text-shadow">Bienvenido a Todo Videojuegos</h1>
            <p class="lead text-white text-shadow">Descubre, compra y comparte tu pasión por los videojuegos</p>
        </div>
    </div>
    <div class="container my-5">
        <h2 class="text-center text-white">Explora nuestros videojuegos mejor valorados</h2>
        <div v-if="cargandoValorados" class="text-center my-4">
            <span class="spinner-border"></span>
        </div>
        <div v-else>
            <div class="row justify-content-center">
                <div v-for="(juego, idx) in mejorValorados" :key="juego.id" class="col-12 col-md-6 col-lg-4 mb-4">
                    <div class="card h-100 bg-light text-dark">
                        <div v-if="juego.imagenes && juego.imagenes.length">
                            <div class="carrusel-img-wrapper">
                                <img v-for="(img, i) in juego.imagenes" :key="img.url" :src="img.url"
                                    class="carrusel-img" :class="{ active: (indicesCarrusel[juego.id] || 0) === i }"
                                    :alt="juego.nombre + ' screenshot'">
                                <span v-if="juego.imagenes[indicesCarrusel[juego.id] || 0].portada"
                                    class="badge bg-primary position-absolute"
                                    style="top:10px;left:10px;z-index:3;">Portada</span>
                                <button v-if="juego.imagenes.length > 1"
                                    class="btn btn-dark position-absolute top-50 start-0 translate-middle-y"
                                    style="z-index:2;" @click="prevImagen(juego.id, juego.imagenes.length)"><i
                                        class="fa-solid fa-chevron-left"></i></button>
                                <button v-if="juego.imagenes.length > 1"
                                    class="btn btn-dark position-absolute top-50 end-0 translate-middle-y"
                                    style="z-index:2;" @click="nextImagen(juego.id, juego.imagenes.length)"><i
                                        class="fa-solid fa-chevron-right"></i></button>
                            </div>
                        </div>
                        <div v-else>
                            <img v-if="juego.imagen" :src="juego.imagen" class="card-img-top" :alt="juego.nombre" style="object-fit:cover;height:200px;">
                            <span v-else>Sin imágenes</span>
                        </div>
                        <div class="card-body">
                            <h5 class="card-title">{{ juego.nombre }}</h5>
                            <p class="card-text mb-1"><strong>Media valoración:</strong> {{ juego.valoracion_media }}/5</p>
                            <p class="card-text mb-1"><strong>Precio:</strong> {{ formatPrice(juego.precio) }} €</p>
                            <p class="card-text mb-1"><strong>Plataforma:</strong> {{ juego.plataforma }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <p class="text-center text-white">Aquí puedes encontrar información y enlaces útiles.</p>
        
    </div>
    <ScrollBotonComponent />
    <PiePaginaComponent />
</template>

<script setup lang="ts">
import MenuComponent from './menuComponent.vue';
import PiePaginaComponent from './piePaginaComponent.vue';
import ScrollBotonComponent from './scrollBotonComponent.vue';
import { ref, onMounted } from 'vue';

const mejorValorados = ref<any[]>([]);
const cargandoValorados = ref(false);
const indicesCarrusel = ref<{ [key: number]: number }>({});

const formatPrice = (price: number): string => {
    if (typeof price !== 'number') {
        return '0,00';
    }
    return price.toLocaleString('es-ES', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
};

const fetchMejorValorados = async () => {
    cargandoValorados.value = true;
    try {
        const token = localStorage.getItem('token');
        const response = await fetch('/api/videojuegos/mejorValorados', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ token })
        });
        const data = await response.json();
        mejorValorados.value = data || [];
    } catch (e) {
        mejorValorados.value = [];
    } finally {
        cargandoValorados.value = false;
    }
};

const prevImagen = (juegoId: number, total: number) => {
    const idx = indicesCarrusel.value[juegoId] || 0;
    indicesCarrusel.value[juegoId] = (idx - 1 + total) % total;
};
const nextImagen = (juegoId: number, total: number) => {
    const idx = indicesCarrusel.value[juegoId] || 0;
    indicesCarrusel.value[juegoId] = (idx + 1) % total;
};

onMounted(() => {
    fetchMejorValorados();
});
</script>

<style scoped>
.video-bg {
    object-fit: cover;
    z-index: 1;
    pointer-events: none;
}

.overlay-bg {
    z-index: 2;
    pointer-events: none;
}

.text-shadow {
    text-shadow: 2px 2px 8px #232a3a;
}
</style>