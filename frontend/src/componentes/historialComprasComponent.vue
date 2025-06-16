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

            <div v-else>
                <div v-for="(compra, index) in compras" :key="index">
                    <div :id="'id:' + index" class="card mb-1 bg-light text-dark">
                        <div class="card-header">
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
                    <button class="btn btn-primary my-2" @click="descargarFactura(compra, index)">
                        Descargar Factura
                    </button>
                </div>
            </div>
        </div>
        <ScrollBotonComponent />
    </div>
    <PiePaginaComponent />
</template>

<script setup lang="ts">
import { ref, onMounted, nextTick } from 'vue';
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
    fecha: string;
    precio_total: number;
    detalles: DetalleCompra[];
}

const compras = ref<Compra[]>([]);
const cargando = ref(false);

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
    } catch (e) {
        console.error(e);
        compras.value = [];
    } finally {
        cargando.value = false;
    }
};

const descargarFactura = async (compra: Compra, index: number) => {
    await nextTick();
    const el = document.getElementById(`id:${index}`);
    if (!el) return;

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