<template>
    <MenuComponent />
    <div class="container my-5 text-white">
        <h2 class="mb-4 text-center">Añadir Videojuego</h2>
        <form class="mb-4 d-flex" @submit.prevent="buscar">
            <input v-model="busqueda" type="search" class="form-control me-2"
                placeholder="Buscar videojuego por nombre">
            <button class="btn btn-success" type="submit">
                <i class="fa-solid fa-magnifying-glass d-inline me-2"></i>
                <span>Buscar</span>
            </button>
            <button v-if="busqueda" class="btn btn-danger ms-2" type="button" @click="limpiarBusqueda">Limpiar</button>
        </form>

        <div v-if="showSuccessMessage"
            class="alert alert-success text-center position-fixed top-50 start-50 translate-middle"
            style="z-index: 1050; width: auto; white-space: nowrap;">
            ¡Videojuego insertado correctamente!
        </div>

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
                            <div class="mb-2">
                                <label class="form-label mb-1">Plataformas:</label>
                                <select class="form-select" v-if="juego.platforms && juego.platforms.length"
                                    v-model="plataformasSeleccionadas[juego.id]">
                                    <option v-for="plataforma in juego.platforms" :key="plataforma.platform.id"
                                        :value="plataforma.platform.name">
                                        {{ plataforma.platform.name }}
                                    </option>
                                </select>
                                <span v-else class="text-muted">No disponible</span>
                            </div>
                            <div class="mb-2">
                                <label class="form-label mb-1">Precio (€):</label>
                                <input type="number" min="0" step="0.01" class="form-control"
                                    v-model="precios[juego.id]" placeholder="Precio" />
                            </div>
                            <div class="mb-2">
                                <label class="form-label mb-1">Stock:</label>
                                <input type="number" min="0" step="1" class="form-control" v-model="stocks[juego.id]"
                                    placeholder="Stock" />
                            </div>
                            <div v-if="errores[juego.id]" class="alert alert-danger py-2 mb-2">{{ errores[juego.id] }}
                            </div>
                            <button class="btn btn-primary mt-auto"
                                @click="anadirJuego(juego, plataformasSeleccionadas[juego.id], precios[juego.id], stocks[juego.id])"
                                :disabled="isAddingGame[juego.id]">
                                <span v-if="isAddingGame[juego.id]" class="spinner-border spinner-border-sm me-2"
                                    role="status" aria-hidden="true"></span>
                                <span v-if="isAddingGame[juego.id]">Añadiendo ...</span>
                                <span v-else>Añadir a mi web</span>
                            </button>
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
        <ScrollBotonComponent />
    </div>
    <PiePaginaComponent />
</template>

<script setup lang="ts">
import { ref, watch, onMounted, nextTick } from 'vue';
import urlBackend from '@/rutaApi';
import MenuComponent from './menuComponent.vue';
import { useRouter } from 'vue-router';
import PiePaginaComponent from './piePaginaComponent.vue';
import ScrollBotonComponent from './scrollBotonComponent.vue';

const juegos = ref<any[]>([]);
const plataformasSeleccionadas = ref<{ [key: number]: string }>({});
const cargando = ref(false);
const pagina = ref(1);
const busqueda = ref("");
const pageSize = 20;
const siguienteHabilitado = ref(true);
const indicesCarrusel = ref<{ [key: number]: number }>({});
const precios = ref<{ [key: number]: number }>({});
const stocks = ref<{ [key: number]: number }>({});
const errores = ref<{ [key: number]: string }>({});
const router = useRouter();

const showSuccessMessage = ref(false);
const isAddingGame = ref<{ [key: number]: boolean }>({});

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
        indicesCarrusel.value = {};
        isAddingGame.value = {};
        await nextTick();
        juegos.value.forEach(juego => {
            if (juego.platforms && juego.platforms.length) {
                plataformasSeleccionadas.value[juego.id] = juego.platforms[0].platform.name;
            } else {
                plataformasSeleccionadas.value[juego.id] = '';
            }
            isAddingGame.value[juego.id] = false;
        });
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
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
});

onMounted(fetchJuegos);

const anadirJuego = async (juego: any, plataformaSeleccionada?: string, precio?: number, stock?: number) => {
    errores.value[juego.id] = '';
    if (precio === undefined || precio === null || isNaN(precio) || precio <= 0) {
        errores.value[juego.id] = 'El precio debe ser un número mayor que 0.';
        return;
    }
    if (stock === undefined || stock === null || isNaN(stock) || stock < 0 || !Number.isInteger(stock)) {
        errores.value[juego.id] = 'El stock debe ser un número entero mayor o igual que 0.';
        return;
    }

    isAddingGame.value[juego.id] = true;

    try {
        const token = localStorage.getItem("token");
        if (!token) {
            alert('Debes iniciar sesión para añadir videojuegos.');
            router.push('/login');
            return;
        }
        const imagenes = (juego.short_screenshots || []).map((img: any) => ({
            url: img.image,
            portada: img.id === -1
        }));
        const generos = (juego.genres || []).map((g: { name: string }) => g.name);
        const response = await fetch(urlBackend + '/api/videojuegos/crear', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                token: token,
                nombre: juego.name,
                precio: precio,
                fecha_lanzamiento: juego.released,
                stock: stock,
                imagenes,
                categorias: generos,
                plataforma: plataformaSeleccionada,
            })
        });
        if (response.ok) {
            showSuccessMessage.value = true;
            setTimeout(() => {
                showSuccessMessage.value = false;
            }, 3000);
        } else {
            const errorData = await response.json();
            errores.value[juego.id] = errorData.message || 'Error al añadir el videojuego.';
        }
    } catch (e) {
        errores.value[juego.id] = 'Error de red o servidor.';
    } finally {
        isAddingGame.value[juego.id] = false;
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