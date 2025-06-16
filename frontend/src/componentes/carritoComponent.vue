<template>
    <MenuComponent />
    <div class="container my-5">
        <h2 class="mb-4 text-center">Carrito de Compras</h2>
        <div v-if="cargando" class="text-center my-5">
            <span class="spinner-border"></span>
        </div>
        <div v-else>
            <div v-if="carrito.length === 0" class="alert alert-info text-center">Tu carrito está vacío.</div>
            <div v-else>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Plataforma</th>
                            <th>Precio</th>
                            <th>Cantidad</th>
                            <th>Total</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in carrito" :key="item.videojuego_id">
                            <td>{{ item.nombre }}</td>
                            <td>{{ item.plataforma }}</td>
                            <td>{{ formatPrice(item.precio) }} €</td>
                            <td>{{ item.cantidad }}</td>
                            <td>{{ formatPrice(item.precio * item.cantidad) }} €</td>
                            <td>
                                <button class="btn btn-danger btn-sm" @click="eliminarDelCarrito(item.videojuego_id)"><i
                                        class="fa fa-trash"></i> Eliminar</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div class="text-end fw-bold">Total: {{ formatPrice(totalCarrito) }} €</div>
            </div>
        </div>
        <ScrollBotonComponent />
    </div>
    <PiePaginaComponent />
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import urlBackend from '@/rutaApi';
import MenuComponent from './MenuComponent.vue';
import ScrollBotonComponent from './scrollBotonComponent.vue';
import PiePaginaComponent from './piePaginaComponent.vue';

const carrito = ref<any[]>([]);
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

const totalCarrito = computed(() => {
    return carrito.value.reduce((acc, item) => acc + (item.precio * item.cantidad), 0);
});

const fetchCarrito = async () => {
    cargando.value = true;
    try {
        const token = localStorage.getItem('token');
        const response = await fetch(urlBackend + '/api/carrito/obtener', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ token })
        });
        const data = await response.json();
        carrito.value = data || [];
    } catch (e) {
        carrito.value = [];
    } finally {
        cargando.value = false;
    }
};

const eliminarDelCarrito = async (videojuegoId: number) => {
    try {
        const token = localStorage.getItem('token');
        const response = await fetch(urlBackend + '/api/carrito/eliminar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ token, videojuegoId })
        });
        const data = await response.json();
        if (data.success) {
            await fetchCarrito(); // Recargar la lista del carrito desde el backend
        }
    } catch (e) {
        // Manejo de error opcional
    }
};

onMounted(() => {
    fetchCarrito();
});
</script>
