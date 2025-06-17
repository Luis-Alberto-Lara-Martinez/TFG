// paypalComponent.vue
<template>
    <div ref="paypalButtonContainer"></div>
</template>

<script setup lang="ts">
import { onMounted, ref, watch } from 'vue';
import { loadScript } from '@paypal/paypal-js';
import urlBackend from '@/rutaApi';

const props = defineProps<{
    total: number;
    carritoItems: any[];
}>();

const paypalButtonContainer = ref<HTMLElement | null>(null);
const paypalLoaded = ref(false);

const loadPaypalScript = async () => {
    try {
        const paypal = await loadScript({
            clientId: "AaNVAE_e5UgmzDVpWe5b4ZuZaDnO9XeTqmBNIhqyYriuc-BFDC6lm3OIrZ9N1mk0e5CHAgTkP0SnV3AW", // Replace with your actual PayPal Client ID
            currency: "EUR"
        });

        if (paypal && paypal.Buttons && paypalButtonContainer.value) {
            if (paypalButtonContainer.value.hasChildNodes()) {
                paypalButtonContainer.value.innerHTML = '';
            }

            await paypal.Buttons({
                createOrder: (data: any, actions: any) => {
                    return actions.order.create({
                        purchase_units: [{
                            amount: {
                                value: props.total.toFixed(2),
                                currency_code: "EUR"
                            },
                        }]
                    });
                },
                onApprove: async (data: any, actions: any) => {
                    const order = await actions.order.capture();
                    console.log('Pago completado:', order);

                    try {
                        const token = localStorage.getItem('token');
                        const response = await fetch(urlBackend + '/api/compras/crear', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                token,
                                transaccion_id: order.id,
                                precio_total: props.total,
                                videojuegos: props.carritoItems.map(item => ({
                                    videojuego_id: item.videojuego_id,
                                    cantidad: item.cantidad,
                                    precio_unitario: item.precio,
                                }))
                            })
                        });
                        const backendResponse = await response.json();
                        emit('payment-success');
                    } catch (error) {
                        console.error('Error al comunicar con el backend:', error);
                        alert('Pago exitoso, pero hubo un error de comunicación con el servidor.');
                    }
                },
                onError: (err: any) => {
                    console.error('Error en el pago de PayPal:', err);
                    alert('Hubo un error con el pago de PayPal. Por favor, inténtalo de nuevo.');
                }
            }).render(paypalButtonContainer.value);
            paypalLoaded.value = true;
        }
    } catch (error) {
        console.error("Error al cargar el script de PayPal:", error);
    }
};

const emit = defineEmits(['payment-success']);

onMounted(() => {
    if (props.total > 0 && props.carritoItems.length > 0) {
        loadPaypalScript();
    }
});

watch([() => props.total, () => props.carritoItems], ([newTotal, newItems]) => {
    if (newTotal > 0 && newItems.length > 0) {
        loadPaypalScript();
    } else if (newTotal === 0 || newItems.length === 0) {
        if (paypalButtonContainer.value) {
            paypalButtonContainer.value.innerHTML = '';
        }
        paypalLoaded.value = false;
    }
}, { deep: true });
</script>