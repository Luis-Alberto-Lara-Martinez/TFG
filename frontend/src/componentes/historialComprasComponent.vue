<template>
    <MenuComponent />
    <div class="container my-5 text-white">
        <h2 class="mb-4 text-center">Historial de Compras</h2>
        <div v-if="cargando" class="text-center my-5">
            <span class="spinner-border"></span>
        </div>
        <div v-else>
            <div v-if="compras.length === 0" class="alert alert-info text-center">No tienes compras registradas.</div>
            <div v-else>
                <div v-for="(compra, index) in compras" :key="index" class="card mb-4 bg-light text-dark">
                    <div class="card-header">
                        <strong>Fecha de compra:</strong> {{ compra.fecha }}
                        <br />
                        <span class=""><strong>Total:</strong> {{ formatPrice(compra.precio_total) }} €</span>
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
                                <span>Total Videojuego: {{ formatPrice(detalle.cantidad *
                                    detalle.precio_unitario) }} €</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <PiePaginaComponent />
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import urlBackend from '@/rutaApi';
import MenuComponent from './MenuComponent.vue';
import PiePaginaComponent from './piePaginaComponent.vue';
// Eliminado: jwtDecode no se usa en este componente.
// import { jwtDecode } from 'jwt-decode';

// Se recomienda definir un tipo para las compras para mejorar la legibilidad y seguridad del código.
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
        maximumFractionDigits: 2
    });
};

const fetchCompras = async () => {
    cargando.value = true;
    try {
        const token = localStorage.getItem('token');
        if (!token) {
            // Es buena práctica manejar el caso en que no haya token.
            console.error("No se encontró el token de autenticación.");
            compras.value = [];
            return;
        }
        const response = await fetch(`${urlBackend}/api/compras/historial`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ token })
        });

        if (!response.ok) {
            throw new Error('Error al obtener el historial de compras');
        }

        const data = await response.json();
        compras.value = data || [];
        console.log(data);
    } catch (e) {
        console.error(e);
        compras.value = [];
    } finally {
        cargando.value = false;
    }
};

onMounted(() => {
    fetchCompras();
});
</script>