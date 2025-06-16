<template>
    <div ref="paypalButtonContainer"></div>
</template>

<script setup lang="ts">
import { onMounted, ref, watch } from 'vue';
import { loadScript } from '@paypal/paypal-js';
import urlBackend from '@/rutaApi'; // Assuming you have this for backend communication

const props = defineProps<{
    total: number;
}>();

const paypalButtonContainer = ref<HTMLElement | null>(null);
const paypalLoaded = ref(false);

const loadPaypalScript = async () => {
    try {
        const paypal = await loadScript({
            clientId: "AaNVAE_e5UgmzDVpWe5b4ZuZaDnO9XeTqmBNIhqyYriuc-BFDC6lm3OIrZ9N1mk0e5CHAgTkP0SnV3AW", // Replace with your actual PayPal Client ID
            currency: "EUR" // Set the currency to EUR
        });

        if (paypal && paypal.Buttons && paypalButtonContainer.value) {
            await paypal.Buttons({
                createOrder: (data: any, actions: any) => {
                    return actions.order.create({
                        purchase_units: [{
                            amount: {
                                value: props.total.toFixed(2), // Ensure two decimal places
                                currency_code: "EUR"
                            }
                        }]
                    });
                },
                onApprove: async (data: any, actions: any) => {
                    const order = await actions.order.capture();
                    console.log('Pago completado:', order);

                    // You might want to send a request to your backend to verify the payment
                    // and update the order status in your database.
                    try {
                        const token = localStorage.getItem('token');
                        const response = await fetch(urlBackend + '/api/pedidos/crearPedido', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                token,
                                orderId: order.id,
                                status: order.status,
                                total: props.total
                            })
                        });
                        const backendResponse = await response.json();
                        if (backendResponse.success) {
                            alert('¡Pago exitoso y pedido registrado!');
                            // Optionally, clear the cart or redirect the user
                            // window.location.reload(); // Or emit an event to parent to refresh cart
                        } else {
                            alert('Pago exitoso, pero hubo un problema al registrar el pedido en el sistema.');
                        }
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

onMounted(() => {
    if (props.total > 0) {
        loadPaypalScript();
    }
});

watch(() => props.total, (newTotal) => {
    if (newTotal > 0 && !paypalLoaded.value) {
        loadPaypalScript();
    }
    // If the total changes after initial load and you want to re-render the button
    // you might need to destroy the old button and re-render a new one.
    // For simplicity, this example reloads only if not loaded initially.
});
</script>