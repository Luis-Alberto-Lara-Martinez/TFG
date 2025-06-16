<template>
    <MenuComponent />
    <div class="container my-5 text-white">
        <h2 class="mb-4 text-center">Listado de Videojuegos</h2>
        <!-- Panel de búsqueda y filtros -->
        <div class="card p-3 mb-4 bg-light text-dark">
            <div class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Buscar por título</label>
                    <input v-model="busquedaTitulo" @keyup.enter="buscarPorTitulo" type="text" class="form-control"
                        placeholder="Introduce el título...">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Ordenar por</label>
                    <select v-model="ordenSeleccionado" class="form-select">
                        <option value="">Sin orden</option>
                        <option value="precio_asc">Precio (ascendente)</option>
                        <option value="precio_desc">Precio (descendente)</option>
                        <option value="nombre_asc">Nombre (A-Z)</option>
                        <option value="nombre_desc">Nombre (Z-A)</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Plataforma</label>
                    <select v-model="plataformaSeleccionada" class="form-select">
                        <option value="">Todas</option>
                        <option v-for="plataforma in plataformas" :key="plataforma" :value="plataforma">{{ plataforma }}
                        </option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Categoría</label>
                    <select v-model="categoriaSeleccionada" class="form-select">
                        <option value="">Todas</option>
                        <option v-for="categoria in categorias" :key="categoria" :value="categoria">{{ categoria }}
                        </option>
                    </select>
                </div>
                <div class="col-md-2 d-grid">
                    <button class="btn btn-primary mt-3" @click="aplicarFiltros">Buscar / Filtrar</button>
                </div>
            </div>
        </div>
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
                            <p class="card-text mb-2"><strong>Precio: </strong>
                                {{ formatPrice(juego.precio) }} €
                            </p>
                            <p class="card-text mb-2"><strong>Categorías: </strong>
                                <span v-if="juego.generos && juego.generos.length">
                                    {{ juego.generos.join(', ') }}
                                </span>
                                <span v-else>No especificadas</span>
                            </p>
                            <p class="card-text mb-2"><strong>Plataforma: </strong>
                                <span>{{ juego.plataforma || 'No especificada' }}</span>
                            </p>
                            <p class="card-text mb-2"><strong>Fecha de lanzamiento: </strong>
                                <span>{{ juego.fecha_lanzamiento || 'No disponible' }}</span>
                            </p>
                            <button class="btn btn-success mt-2" @click="anadirAlCarrito(juego.id)">Añadir al
                                carrito</button>
                            <button class="btn btn-info mt-2" @click="irAResenas(juego.id)">Ver reseñas</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="d-flex justify-content-center mt-4">
                <button class="btn btn-primary me-2" :disabled="pagina === 1"
                    @click="pagina--; scrollArriba()">Anterior</button>
                <span class="align-self-center">Página {{ pagina }}</span>
                <button class="btn btn-primary ms-2" :disabled="pagina === totalPaginas"
                    @click="pagina++; scrollArriba()">Siguiente</button>
            </div>
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

const fetchVideojuegos = async () => {
    cargando.value = true;
    try {
        const token = localStorage.getItem('token');
        const response = await fetch(urlBackend + '/api/videojuegos/listarVideojuegos', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ token })
        });
        const data = await response.json();
        videojuegos.value = data || [];
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

const buscarPorTitulo = async () => {
    cargando.value = true;
    try {
        const token = localStorage.getItem('token');
        const response = await fetch(urlBackend + '/api/videojuegos/buscarPorTitulo', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ token, titulo: busquedaTitulo.value })
        });
        const data = await response.json();
        videojuegos.value = data || [];
    } catch (e) {
        videojuegos.value = [];
    } finally {
        cargando.value = false;
    }
};

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

const aplicarFiltros = () => {
    pagina.value = 1;
    let filtrados = [...videojuegos.value];
    if (plataformaSeleccionada.value) {
        filtrados = filtrados.filter(j => j.plataforma === plataformaSeleccionada.value);
    }
    if (categoriaSeleccionada.value) {
        filtrados = filtrados.filter(j => (j.categorias || []).includes(categoriaSeleccionada.value));
    }
    videojuegos.value = filtrados;
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

const irAResenas = (id: number) => {
    router.push({ name: 'resenas', params: { productoId: id } });
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