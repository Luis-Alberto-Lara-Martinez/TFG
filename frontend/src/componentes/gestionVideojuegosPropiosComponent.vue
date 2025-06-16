<template>
    <MenuComponent />
    <div class="container my-5 text-white">
        <h2 class="mb-4 text-center">Videojuegos de mi base de datos</h2>
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
                                <span>{{ juego.plataforma }}</span>
                            </p>
                            <p class="card-text mb-2"><strong>Nota media: </strong>
                                <span v-if="juego.nota_media">{{ formatPrice(juego.nota_media) }}
                                    <i class="fa-solid fa-star text-warning"></i>
                                </span>
                                <span v-else>Sin nota</span>
                            </p>
                            <p class="card-text mb-2"><strong>Precio: </strong>
                                <span v-if="editandoId !== juego.id">{{ formatPrice(juego.precio) }} €</span>
                                <input v-else type="number" min="0" step="0.01"
                                    class="form-control d-inline w-auto ms-2" v-model.number="precioEdit" />
                            </p>
                            <p class="card-text mb-2"><strong>Stock: </strong>
                                <span v-if="editandoId !== juego.id">{{ juego.stock }}</span>
                                <input v-else type="number" min="0" step="1" class="form-control d-inline w-auto ms-2"
                                    v-model.number="stockEdit" />
                            </p>
                            <div class="mb-3">
                                <strong>Estado: </strong>
                                <span v-if="editandoId === juego.id">
                                    <select v-model="estadoEdit"
                                        class="form-select form-select-sm w-auto d-inline ms-2">
                                        <option :value="false">Activo</option>
                                        <option :value="true">Inactivo</option>
                                    </select>
                                </span>
                                <span v-else>
                                    <span v-if="juego.deleted" class="badge bg-danger">Inactivo</span>
                                    <span v-else class="badge bg-success">Activo</span>
                                </span>
                            </div>
                            <div class="mt-auto d-flex">
                                <button v-if="editandoId !== juego.id" class="btn btn-warning btn-sm"
                                    @click="empezarEdicion(juego)">
                                    <i class="fa fa-edit me-1"></i>Editar
                                </button>
                                <button v-else class="btn btn-success btn-sm me-1" @click="guardarEdicion(juego)"
                                    :disabled="cargandoEdicion">
                                    <span v-if="cargandoEdicion" class="spinner-border spinner-border-sm me-2"></span>
                                    <i v-else class="fa fa-save me-1"></i>
                                    Guardar
                                </button>
                                <button v-if="editandoId === juego.id" class="btn btn-secondary btn-sm"
                                    @click="cancelarEdicion">
                                    <i class="fa fa-times me-1"></i>Cancelar
                                </button>
                                <button class="btn btn-danger btn-sm ms-auto" data-bs-toggle="modal"
                                    data-bs-target="#modalEliminar" @click="abrirModalEliminar(juego)">
                                    <i class="fa fa-trash me-1"></i>Eliminar
                                </button>
                            </div>
                            <div v-if="errores[juego.id] && editandoId === juego.id"
                                class="alert alert-danger py-2 mb-2">{{
                                errores[juego.id] }}</div>
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

    <div class="modal fade" id="modalEliminar" tabindex="-1" aria-labelledby="modalEliminarLabel" aria-hidden="true"
        ref="modalEliminarRef">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEliminarLabel">Confirmar eliminación</h5>
                </div>
                <div class="modal-body">
                    ¿Seguro que quieres eliminar el videojuego <strong>{{ juegoAEliminar?.nombre }}</strong>?
                    <div v-if="errores[juegoAEliminar?.id]" class="alert alert-danger mt-3">{{
                        errores[juegoAEliminar?.id] }}</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" @click="confirmarEliminar"
                        :disabled="cargandoEliminacion">
                        <span v-if="cargandoEliminacion" class="spinner-border spinner-border-sm me-1"></span>
                        Eliminar
                    </button>
                </div>
            </div>
        </div>
    </div>
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
        const response = await fetch(urlBackend + '/api/videojuegos/listarVideojuegosAdmin', {
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

const editandoId = ref<number | null>(null);
const precioEdit = ref<number>(0);
const stockEdit = ref<number>(0);
const estadoEdit = ref(false);
const nombreEdit = ref('');
const plataformaEdit = ref('');
const errores = ref<{ [key: number]: string }>({});
const juegoAEliminar = ref<any | null>(null);
const modalEliminarRef = ref<any>(null);
let modalEliminarInstance: any = null;
const cargandoEdicion = ref(false);
const cargandoEliminacion = ref(false);

const empezarEdicion = (juego: any) => {
    editandoId.value = juego.id;
    nombreEdit.value = juego.nombre;
    plataformaEdit.value = juego.plataforma;
    precioEdit.value = juego.precio;
    stockEdit.value = juego.stock;
    estadoEdit.value = !!juego.deleted;
    errores.value[juego.id] = '';
};
const cancelarEdicion = () => {
    editandoId.value = null;
};
const guardarEdicion = async (juego: any) => {
    cargandoEdicion.value = true;
    errores.value[juego.id] = '';
    try {
        const token = localStorage.getItem('token');
        const body = {
            precio: precioEdit.value,
            stock: stockEdit.value,
            deleted: estadoEdit.value,
            token
        };
        const response = await fetch(urlBackend + '/api/videojuegos/editar/' + juego.id, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        });
        const data = await response.json();
        if (data.error) {
            errores.value[juego.id] = data.error;
        } else {
            await fetchVideojuegos();
            editandoId.value = null;
        }
    } catch (e) {
        errores.value[juego.id] = 'Error al guardar los cambios';
    } finally {
        cargandoEdicion.value = false;
    }
};
const abrirModalEliminar = (juego: any) => {
    juegoAEliminar.value = juego;
    errores.value[juego.id] = '';
};
const confirmarEliminar = async () => {
    cargandoEliminacion.value = true;
    if (!juegoAEliminar.value) return;
    const juego = juegoAEliminar.value;
    errores.value[juego.id] = '';
    try {
        const token = localStorage.getItem('token');
        const response = await fetch(urlBackend + `/api/videojuegos/eliminar/${juego.id}`, {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ token: token })
        });
        if (response.ok) {
            videojuegos.value = videojuegos.value.filter(v => v.id !== juego.id);
            location.reload();
        } else {
            const data = await response.json();
            errores.value[juego.id] = data?.error || 'Error al eliminar el videojuego';
        }
    } catch (e) {
        errores.value[juego.id] = 'Error de red o servidor';
    } finally {
        cargandoEliminacion.value = false;
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
    // Comprobación de rol administrador
    const token = localStorage.getItem('token');
    const rolToken = jwtDecode<any>(localStorage.getItem('token') || '').roles[0];
    if (rolToken != "administrador") {
        router.push('/inicio');
    } else {
        fetchVideojuegos();
    }
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