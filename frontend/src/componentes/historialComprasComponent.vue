<template>
    <MenuComponent />
    <div class="container my-5 text-white">
        <h2 class="mb-4 text-center">Historial de Compras</h2>

        <div v-if="cargando" class="text-center my-5">
            <span class="spinner-border"></span>
        </div>

        <div v-else>
            <div v-if="compras.length === 0" class="alert alert-info text-center">
                No tienes compras registradas
            </div>

            <div v-else id="purchase-history-section">
                <div v-for="(compra, index) in paginatedCompras" :key="index">
                    <div :id="'id:' + (index + (currentPage - 1) * comprasPerPage)"
                        class="card mb-1 bg-light text-dark">
                        <div class="card-header">
                            <strong>Usuario:</strong> {{ compra.usuario }}
                            <br />
                            <strong>Transacción:</strong> {{ compra.transaccion_id }}
                            <br />
                            <strong>Fecha de compra:</strong> {{ compra.fecha }}
                            <br />
                            <strong>Total:</strong> {{ formatPrice(compra.precio_total) }} €
                            <br />
                        </div>
                        <div class="card-body">
                            <h5 class="card-title">Detalles de la compra</h5>
                            <ul class="list-group list-group-flush">
                                <li v-for="(detalle, detalleIndex) in compra.detalles" :key="detalleIndex"
                                    class="list-group-item bg-light">
                                    <strong>{{ detalle.videojuego }}</strong>
                                    <span> - {{ detalle.plataforma }}</span>
                                    <br />
                                    <span>Cantidad: {{ detalle.cantidad }}</span>
                                    <br />
                                    <span>Precio Unitario: {{ formatPrice(detalle.precio_unitario) }} €</span>
                                    <br />
                                    <span>
                                        Total Videojuego:
                                        {{ formatPrice(detalle.cantidad * detalle.precio_unitario) }} €
                                    </span>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <button class="btn btn-primary my-2"
                        @click="descargarFactura(compra, index + (currentPage - 1) * comprasPerPage)">
                        Descargar Factura
                    </button>
                </div>

                <nav v-if="totalPages > 1">
                    <ul class="pagination justify-content-center mt-4">
                        <li class="page-item me-2" :class="{ disabled: currentPage === 1 }">
                            <button class="btn btn-primary" @click="prevPage"
                                :disabled="currentPage === 1">Anterior</button>
                        </li>
                        <li class="page-item mx-2 d-flex align-items-center text-white">
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
import { ref, onMounted, computed, nextTick } from 'vue';
// @ts-ignore
import html2pdf from 'html2pdf.js';
import urlBackend from '@/rutaApi';
import MenuComponent from './menuComponent.vue';
import PiePaginaComponent from './piePaginaComponent.vue';
import ScrollBotonComponent from './scrollBotonComponent.vue';

interface DetalleCompra {
    videojuego: string;
    plataforma: string;
    cantidad: number;
    precio_unitario: number;
}

interface Compra {
    usuario: string;
    transaccion_id: string;
    fecha: string;
    precio_total: number;
    detalles: DetalleCompra[];
}

const compras = ref<Compra[]>([]);
const cargando = ref(false);

const currentPage = ref(1);
const comprasPerPage = 5;

const paginatedCompras = computed(() => {
    const start = (currentPage.value - 1) * comprasPerPage;
    const end = start + comprasPerPage;
    return compras.value.slice(start, end);
});

const totalPages = computed(() => {
    return Math.ceil(compras.value.length / comprasPerPage);
});

const scrollToPurchaseHistory = async () => {
    await nextTick();
    const purchaseHistorySection = document.getElementById('purchase-history-section');
    if (purchaseHistorySection) {
        purchaseHistorySection.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
};

const prevPage = () => {
    if (currentPage.value > 1) {
        currentPage.value--;
        scrollToPurchaseHistory();
    }
};

const nextPage = () => {
    if (currentPage.value < totalPages.value) {
        currentPage.value++;
        scrollToPurchaseHistory();
    }
};

const formatPrice = (price: number): string => {
    if (typeof price !== 'number') {
        return '0,00';
    }
    return price.toLocaleString('es-ES', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
};

const fetchCompras = async () => {
    cargando.value = true;
    try {
        const token = localStorage.getItem('token');
        if (!token) {
            console.error('No se encontró el token de autenticación.');
            compras.value = [];
            return;
        }

        const response = await fetch(`${urlBackend}/api/compras/historial`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ token }),
        });

        if (!response.ok) {
            throw new Error('Error al obtener el historial de compras');
        }

        const data = await response.json();
        compras.value = data || [];
        currentPage.value = 1;
    } catch (e) {
        console.error(e);
        compras.value = [];
    } finally {
        cargando.value = false;
    }
};

const descargarFactura = async (compra: Compra, originalIndex: number) => {
    await nextTick();
    const el = document.getElementById(`id:${originalIndex}`);
    if (!el) {
        console.error(`Element with ID id:${originalIndex} not found.`);
        return;
    }

    const opt = {
        margin: 0.5,
        filename: `factura_${compra.fecha.replace(/[: ]/g, '_')}.pdf`,
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2 },
        jsPDF: { unit: 'in', format: 'letter', orientation: 'portrait' },
    };

    html2pdf().set(opt).from(el).save();
};

onMounted(() => {
    fetchCompras();
});
</script>

<style scoped>
.text-white .pagination .page-item .text-white {
    color: white !important;
}
</style>