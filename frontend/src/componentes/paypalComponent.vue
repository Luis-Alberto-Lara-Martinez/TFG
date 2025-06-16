// paypalComponent.vue
<template>
    <div ref="paypalButtonContainer"></div>
</template>

<script setup lang="ts">
import { onMounted, ref, watch } from 'vue';
import { loadScript } from '@paypal/paypal-js';
import urlBackend from '@/rutaApi'; // Assuming you have this for backend communication

const props = defineProps<{
    total: number;
    carritoItems: any[]; // NEW PROP: Accept the full cart array
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
            // Clear existing buttons before rendering new ones if the total changes
            if (paypalButtonContainer.value.hasChildNodes()) {
                paypalButtonContainer.value.innerHTML = '';
            }

            await paypal.Buttons({
                createOrder: (data: any, actions: any) => {
                    return actions.order.create({
                        purchase_units: [{
                            amount: {
                                value: props.total.toFixed(2), // Ensure two decimal places
                                currency_code: "EUR"
                            },
                            // Optionally, you can add item breakdown here for PayPal's view
                            // items: props.carritoItems.map(item => ({
                            //     name: item.nombre,
                            //     quantity: item.cantidad.toString(),
                            //     unit_amount: {
                            //         currency_code: "EUR",
                            //         value: item.precio.toFixed(2)
                            //     }
                            // }))
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
                                // NEW: Pass the full cart items to the backend
                                videojuegos: props.carritoItems.map(item => ({
                                    videojuego_id: item.videojuego_id,
                                    cantidad: item.cantidad,
                                    precio_unitario: item.precio, // It's good to also send the price at the time of purchase
                                    // You might also want to send:
                                    // nombre: item.nombre,
                                    // plataforma: item.plataforma
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

// Define emits
const emit = defineEmits(['payment-success']);

onMounted(() => {
    if (props.total > 0 && props.carritoItems.length > 0) { // Also check if there are items
        loadPaypalScript();
    }
});

watch([() => props.total, () => props.carritoItems], ([newTotal, newItems]) => {
    // Re-render PayPal button if total or items change significantly
    if (newTotal > 0 && newItems.length > 0) {
        // To properly re-render the PayPal button, you often need to destroy the old instance
        // before rendering a new one. This often involves clearing the container.
        // The `loadPaypalScript` function now includes logic to clear the container.
        loadPaypalScript();
    } else if (newTotal === 0 || newItems.length === 0) {
        // If cart becomes empty, clear the PayPal button
        if (paypalButtonContainer.value) {
            paypalButtonContainer.value.innerHTML = '';
        }
        paypalLoaded.value = false;
    }
}, { deep: true }); // Use deep watch for carritoItems
</script>