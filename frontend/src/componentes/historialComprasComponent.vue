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
                <div v-for="compra in compras" :key="compra.id" class="card mb-4 bg-light text-dark">
                    <div class="card-header">
                        <strong>Fecha de compra:</strong> {{ compra.fecha }}
                        <span class="float-end"><strong>Total:</strong> {{ formatPrice(compra.total) }} €</span>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title">Detalles de la compra</h5>
                        <ul class="list-group list-group-flush">
                            <li v-for="detalle in compra.detalles" :key="detalle.id" class="list-group-item bg-light">
                                <strong>{{ detalle.titulo }}</strong> - {{ detalle.cantidad }} x {{ formatPrice(detalle.precio) }} €
                                <span v-if="detalle.plataforma"> | Plataforma: {{ detalle.plataforma }}</span>
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
import MenuComponent from './menuComponent.vue';
import PiePaginaComponent from './piePaginaComponent.vue';
import { jwtDecode } from 'jwt-decode';

const compras = ref<any[]>([]);
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
        const response = await fetch(urlBackend + '/api/compras/historial', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ token })
        });
        const data = await response.json();
        compras.value = data || [];
    } catch (e) {
        compras.value = [];
    } finally {
        cargando.value = false;
    }
};

onMounted(() => {
    fetchCompras();
});
</script>