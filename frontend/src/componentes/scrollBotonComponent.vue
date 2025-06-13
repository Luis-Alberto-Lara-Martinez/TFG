<template>
    <button v-show="show" class="btn btn-primary position-fixed scroll-top-btn" @click="scrollToTop">
        <i class="fa-solid fa-arrow-up fa-xs"></i>
    </button>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
const show = ref(false);
const handleScroll = () => {
    show.value = window.scrollY > 200;
};
const scrollToTop = () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
};
onMounted(() => {
    window.addEventListener('scroll', handleScroll);
});
onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
});
</script>

<style scoped>
.scroll-top-btn {
    bottom: 32px;
    right: 32px;
    z-index: 9999;
    border-radius: 50%;
    width: 48px;
    height: 48px;
    font-size: 2rem;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    opacity: 0.85;
    transition: opacity 0.2s;
}
</style>
