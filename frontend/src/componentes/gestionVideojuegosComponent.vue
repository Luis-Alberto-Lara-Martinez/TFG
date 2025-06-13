<template>
    <MenuComponent />
    <div class="container my-5 text-white">
        <h2 class="mb-4 text-center">Gestión de Videojuegos (RAWG API)</h2>
        <form class="mb-4 d-flex" @submit.prevent="buscar">
            <input v-model="busqueda" type="search" class="form-control me-2"
                placeholder="Buscar videojuego por nombre">
            <button class="btn btn-success" type="submit">
                <i class="fa-solid fa-magnifying-glass d-inline me-2"></i>
                <span>Buscar</span>
            </button>
            <button v-if="busqueda" class="btn btn-danger ms-2" type="button" @click="limpiarBusqueda">Limpiar</button>
        </form>
        <div v-if="cargando" class="text-center my-5">
            <span class="spinner-border"></span>
        </div>
        <div v-else>
            <div class="row g-4">
                <div v-for="(juego, idxJuego) in juegos" :key="juego.id" class="col-12 col-md-6 col-lg-4">
                    <div class="card h-100 bg-white text-dark">
                        <div v-if="juego.short_screenshots && juego.short_screenshots.length">
                            <div class="carrusel-img-wrapper">
                                <img v-for="(img, idx) in juego.short_screenshots" :key="img.id" :src="img.image"
                                    class="carrusel-img" :class="{
                                        active: (indicesCarrusel[juego.id] || 0) === idx,
                                        left: (indicesCarrusel[juego.id] || 0) > idx,
                                        right: (indicesCarrusel[juego.id] || 0) < idx
                                    }" :alt="juego.name + ' screenshot'">
                                <span v-if="juego.short_screenshots[indicesCarrusel[juego.id] || 0].id === -1"
                                    class="badge bg-primary position-absolute"
                                    style="top:10px;left:10px;z-index:3;">Portada</span>
                                <button v-if="juego.short_screenshots.length > 1"
                                    class="btn btn-dark position-absolute top-50 start-0 translate-middle-y"
                                    style="z-index:2;" @click="prevImagen(juego.id, juego.short_screenshots.length)"><i
                                        class="fa-solid fa-chevron-left"></i></button>
                                <button v-if="juego.short_screenshots.length > 1"
                                    class="btn btn-dark position-absolute top-50 end-0 translate-middle-y"
                                    style="z-index:2;" @click="nextImagen(juego.id, juego.short_screenshots.length)"><i
                                        class="fa-solid fa-chevron-right"></i></button>
                            </div>
                        </div>
                        <div v-else>
                            <img :src="juego.background_image" class="card-img-top" :alt="juego.name"
                                v-if="juego.background_image">
                        </div>
                        <div class="card-body d-flex flex-column text-dark">
                            <h5 class="card-title">{{ juego.name }}</h5>
                            <p class="card-text mb-2"><strong>Fecha de lanzamiento:</strong> {{ juego.released }}</p>
                            <p class="card-text mb-2"><strong>Rating:</strong> {{ juego.rating }}</p>
                            <button class="btn btn-primary mt-auto" @click="anadirJuego(juego)">Añadir a mi web</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="d-flex justify-content-center mt-4">
                <button class="btn btn-primary me-2" :disabled="pagina === 1" @click="pagina--">Anterior</button>
                <span class="align-self-center">Página {{ pagina }}</span>
                <button class="btn btn-primary ms-2" :disabled="!siguienteHabilitado"
                    @click="pagina++">Siguiente</button>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, watch, onMounted } from 'vue';
import urlBackend from '@/rutaApi';
import MenuComponent from './menuComponent.vue';
import { useRouter } from 'vue-router';

const juegos = ref<any[]>([]);
const cargando = ref(false);
const pagina = ref(1);
const busqueda = ref("");
const pageSize = 20;
const siguienteHabilitado = ref(true);
const indicesCarrusel = ref<{ [key: number]: number }>({});
const router = useRouter();

const fetchJuegos = async () => {
    cargando.value = true;
    try {
        let url = `https://api.rawg.io/api/games?key=787d6aa3053f4fefb2cc60f50a1b6b4c&page=${pagina.value}`;
        if (busqueda.value) {
            url += `&search=${encodeURIComponent(busqueda.value)}&search_precise=true`;
        }
        const response = await fetch(url);
        const data = await response.json();
        juegos.value = data.results;
        siguienteHabilitado.value = (data.results && data.results.length === pageSize);
        // Reinicia los índices del carrusel para cada juego
        indicesCarrusel.value = {};
    } catch (e) {
        juegos.value = [];
    } finally {
        cargando.value = false;
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

const buscar = () => {
    pagina.value = 1;
    fetchJuegos();
};

const limpiarBusqueda = () => {
    busqueda.value = "";
    pagina.value = 1;
    fetchJuegos();
};

watch(pagina, () => {
    fetchJuegos();
});

onMounted(fetchJuegos);

const anadirJuego = async (juego: any) => {
    try {
        // Verifica si el usuario está autenticado antes de intentar añadir un juego
        const token = localStorage.getItem("token");
        if (!token) {
            // Simular un alert con un modal simple si no está autenticado
            alert('Debes iniciar sesión para añadir videojuegos.');
            router.push('/login'); // Redirige al login si no hay token
            return;
        }

        const response = await fetch(urlBackend + '/api/videojuegos', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}` // Añadir el token de autenticación
            },
            body: JSON.stringify({
                nombre: juego.name,
                imagen: juego.background_image,
                fecha_lanzamiento: juego.released,
                rating: juego.rating,
                rawg_id: juego.id,
                // Añade más campos si tu backend los requiere, como plataformas, géneros, etc.
            })
        });

        if (response.ok) {
            // Usar un modal personalizado en lugar de alert()
            alert('¡Videojuego añadido a tu web correctamente!'); // Reemplazar con un modal
        } else if (response.status === 401) {
            alert('No autorizado. Por favor, inicia sesión de nuevo.'); // Reemplazar con un modal
            localStorage.removeItem("token");
            router.push('/login');
        } else {
            const errorData = await response.json();
            alert(`Error al añadir el videojuego: ${errorData.message || response.statusText}`); // Reemplazar con un modal
        }
    } catch (e) {
        console.error("Error de red o servidor al añadir videojuego:", e);
        alert('Error de red o servidor al añadir el videojuego.'); // Reemplazar con un modal
    }
};
</script>

<style scoped>
.card-img-top {
    object-fit: cover;
    height: 220px;
    transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}

.carrusel-img-wrapper {
    position: relative;
    width: 100%;
    height: 220px;
    overflow: hidden;
}

.carrusel-img {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    transform: translateX(100%);
    transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}

.carrusel-img.active {
    opacity: 1;
    transform: translateX(0);
    z-index: 2;
}

.carrusel-img.left {
    transform: translateX(-100%);
}

.carrusel-img.right {
    transform: translateX(100%);
}
</style>
