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
    <div class="container my-5 text-white">
        <h2 class="mb-4 text-center">Descubre nuestras últimas novedades</h2>
        <div v-if="cargando" class="text-center my-5">
            <span class="spinner-border"></span>
        </div>
        <div v-else>
            <div class="row g-4">
                <div v-for="juego in videojuegosPaginados" :key="juego.id" class="col-12 col-md-6 col-lg-4">
                    <div class="card h-100 bg-white text-dark">
                        <div v-if="juego.imagenes && juego.imagenes.length">
                            <div class="carrusel-img-wrapper">
                                <img v-for="(img, idx) in juego.imagenes" :key="img.url" :src="img.url"
                                    class="carrusel-img" :class="{
                                        active: (indicesCarrusel[juego.id] || 0) === idx
                                    }" :alt="juego.nombre + ' screenshot'">
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
                            <span>Sin imágenes</span>
                        </div>
                        <div class="card-body d-flex flex-column text-dark">
                            <h5 class="card-title">{{ juego.nombre }}</h5>
                            <p class="card-text mb-2"><strong>Plataforma: </strong>
                                <span>{{ juego.plataforma || 'No especificada' }}</span>
                            </p>
                            <p class="card-text mb-2"><strong>Precio: </strong>
                                {{ formatPrice(juego.precio) }} €
                            </p>
                            <p class="card-text mb-2"><strong>Categorías: </strong>
                                <span v-if="juego.categorias && juego.categorias.length">
                                    {{ juego.categorias.join(', ') }}
                                </span>
                                <span v-else>No especificadas</span>
                            </p>

                            <p class="card-text mb-2"><strong>Fecha de lanzamiento: </strong>
                                <span>{{ juego.fecha_lanzamiento || 'No disponible' }}</span>
                            </p>
                            <p class="card-text mb-2"><strong>Nota media: </strong>
                                <span v-if="juego.nota_media">{{ formatPrice(juego.nota_media) }}
                                    <i class="fa-solid fa-star text-warning"></i>
                                </span>
                                <span v-else>Sin nota</span>
                            </p>
                            <p v-if="juego.stock < 10" class="card-text mb-3 mt-1 alert alert-warning">
                                <strong>Quedan {{ juego.stock }} unidades disponibles</strong>
                            </p>
                            <button class="btn btn-success mt-auto" @click="insertarAlCarrito(juego.id)"
                                :disabled="addingToCart[juego.id]">
                                <span v-if="addingToCart[juego.id]" class="spinner-border spinner-border-sm"
                                    role="status" aria-hidden="true"></span>
                                <span v-else>Añadir al carrito</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="showAlert"
            :class="['alert', alertType, 'position-fixed', 'top-50', 'start-50', 'translate-middle-x']" role="alert"
            style="z-index: 1050; min-width: 300px; max-width: 500px;">
            {{ alertMessage }}
        </div>

        <ScrollBotonComponent />
    </div>
    <PiePaginaComponent />
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import urlBackend from '@/rutaApi';
import MenuComponent from './menuComponent.vue';
import { jwtDecode } from 'jwt-decode';
import { useRouter } from 'vue-router';
import ScrollBotonComponent from './scrollBotonComponent.vue';
import PiePaginaComponent from './piePaginaComponent.vue';

const videojuegos = ref<any[]>([]);
const cargando = ref(false);
const pagina = ref(1);
const porPagina = 9;
const router = useRouter();

const totalPaginas = computed(() => Math.ceil(videojuegos.value.length / porPagina));
const videojuegosPaginados = computed(() => {
    const start = (pagina.value - 1) * porPagina;
    return videojuegos.value.slice(start, start + porPagina);
});

const addingToCart = ref<{ [key: number]: boolean }>({}); // New: To track if a game is being added to cart
const alertMessage = ref(''); // New: Message for the alert
const showAlert = ref(false); // New: Visibility of the alert
const alertType = ref(''); // New: Type of alert (success or danger)

const insertarAlCarrito = async (id: number) => {
    // Prevent multiple clicks for the same item
    if (addingToCart.value[id]) {
        return;
    }

    addingToCart.value[id] = true; // Set to true to disable button and show spinner
    showAlert.value = false; // Hide any previous alerts

    try {
        const token = localStorage.getItem('token');
        const response = await fetch(urlBackend + '/api/videojuegos/insertarAlCarrito', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                token,
                videojuego_id: id,
                cantidad: 1
            })
        });
        const data = await response.json();

        if (response.ok && !data.error) { // Check if response is successful (status 2xx) and no 'error' field
            alertMessage.value = data.message || 'Videojuego añadido al carrito exitosamente.';
            alertType.value = 'alert-success';
        } else {
            alertMessage.value = data.error || 'Hubo un error al añadir el videojuego al carrito.';
            alertType.value = 'alert-danger';
        }
        showAlert.value = true;

        // Automatically hide the alert after 3 seconds
        setTimeout(() => {
            showAlert.value = false;
        }, 3000);

    } catch (e) {
        alertMessage.value = 'Error de conexión. No se pudo añadir el videojuego al carrito.';
        alertType.value = 'alert-danger';
        showAlert.value = true;
        setTimeout(() => {
            showAlert.value = false;
        }, 3000);
    } finally {
        addingToCart.value[id] = false; // Re-enable the button
    }
};

const fetchVideojuegos = async () => {
    cargando.value = true;
    try {
        const token = localStorage.getItem('token');
        const response = await fetch(urlBackend + '/api/videojuegos/ultimasNovedades', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ token })
        });
        const data = await response.json();
        videojuegos.value = data || [];
        console.log(data);
    } catch (e) {
        videojuegos.value = [];
    } finally {
        cargando.value = false;
    }
};

const indicesCarrusel = ref<{ [key: number]: number }>({});
const prevImagen = (juegoId: number, total: number) => {
    const idx = indicesCarrusel.value[juegoId] || 0;
    indicesCarrusel.value[juegoId] = (idx - 1 + total) % total;
};
const nextImagen = (juegoId: number, total: number) => {
    const idx = indicesCarrusel.value[juegoId] || 0;
    indicesCarrusel.value[juegoId] = (idx + 1) % total;
};

const juegoAEliminar = ref<any | null>(null);
const modalEliminarRef = ref<any>(null);
let modalEliminarInstance: any = null;
const cargandoEliminacion = ref(false);

const busquedaTitulo = ref('');
const ordenSeleccionado = ref('');
const plataformaSeleccionada = ref('');
const categoriaSeleccionada = ref('');
const plataformas = ref<string[]>([]);
const categorias = ref<string[]>([]);
const resenasId = ref<number | null>(null);

const obtenerPlataformas = async () => {
    try {
        const token = localStorage.getItem('token');
        const response = await fetch(urlBackend + '/api/videojuegos/plataformas', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ token })
        });
        const data = await response.json();
        plataformas.value = data || [];
    } catch (e) {
        plataformas.value = [];
    }
};

const obtenerCategorias = async () => {
    try {
        const token = localStorage.getItem('token');
        const response = await fetch(urlBackend + '/api/videojuegos/categorias', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ token })
        });
        const data = await response.json();
        categorias.value = data || [];
    } catch (e) {
        categorias.value = [];
    }
};

const aplicarFiltros = async () => {
    // Se inicia la carga
    cargando.value = true;
    try {
        const token = localStorage.getItem('token');
        // ❗ CORRECCIÓN: Se apunta a la ruta correcta del controlador '/aplicarFiltros'
        const response = await fetch(urlBackend + '/api/videojuegos/filtros', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                token,
                titulo: busquedaTitulo.value.trim(),
                orden: ordenSeleccionado.value,
                categoria: categoriaSeleccionada.value,
                plataforma: plataformaSeleccionada.value
            })
        });
        const data = await response.json();
        // ❗ CORRECCIÓN: El resultado se asigna a la variable 'videojuegos'
        videojuegos.value = data || [];
    } catch (e) {
        videojuegos.value = [];
    } finally {
        cargando.value = false;
    }
};

const formatPrice = (price: number): string => {
    if (typeof price !== 'number') {
        return '0,00';
    }
    return price.toLocaleString('es-ES', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
};

function scrollArriba() {
    window.scrollTo({ top: 0, behavior: "instant" });
}

onMounted(() => {
    fetchVideojuegos();
    obtenerPlataformas();
    obtenerCategorias();
});
</script>

<style scoped>
.video-bg {
    object-fit: cover;
    z-index: 3;
    pointer-events: none;
}

.overlay-bg {
    z-index: 4;
    pointer-events: none;
}

.text-shadow {
    text-shadow: 2px 2px 8px #232a3a;
}

.card-img-top {
    object-fit: cover;
    height: 220px;
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
    z-index: 1;
    transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}

.carrusel-img.active {
    opacity: 1;
    z-index: 2;
    transform: translateX(0);
}
</style>