<template>
    <div class="inicio">
        <h1>Bienvenido a la Aplicación</h1>
        <p>Esta es la página de inicio.</p>
        <router-link to="/login">Iniciar Sesión</router-link>
    </div>
</template>

<script>
export default {
    name: 'InicioComponent',
};
</script>