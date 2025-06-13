<template>
    <MenuComponent />
    <div class="position-relative w-100 bg-dark" style="height: 100vh; overflow: hidden;">
        <video src="/portada.mp4" autoplay muted loop playsinline
            class="vw-100 vh-100 position-absolute top-0 start-0 video-bg" />
        <div
            class="position-absolute top-0 start-0 w-100 h-100 d-flex flex-column justify-content-center align-items-center overlay-bg">
            <h1 class="display-4 text-white fw-bold text-shadow">Bienvenido a Todo Videojuegos</h1>
            <p class="lead text-white text-shadow">Descubre, compra y comparte tu pasión por los videojuegos</p>
        </div>
    </div>
    <div class="container my-5">
        <h2 class="text-center text-white">Explora nuestras secciones</h2>
        <p class="text-center text-white">Aquí puedes encontrar información y enlaces útiles.</p>
    </div>
    <ScrollBotonComponent />
    <PiePaginaComponent />
</template>

<script setup lang="ts">
import MenuComponent from './menuComponent.vue';
import PiePaginaComponent from './piePaginaComponent.vue';
import ScrollBotonComponent from './scrollBotonComponent.vue';
</script>

<style scoped>
.video-bg {
    object-fit: cover;
    z-index: 1;
    pointer-events: none;
}

.overlay-bg {
    z-index: 2;
    pointer-events: none;
}

.text-shadow {
    text-shadow: 2px 2px 8px #232a3a;
}
</style>