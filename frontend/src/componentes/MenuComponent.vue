<template>
    <div>
        <nav class="navbar navbar-expand-lg navbar-light shadow-sm">
            <div class="container d-flex justify-content-between">
                <router-link class="navbar-brand" to="/inicio">
                    <img src="favicon.png" alt="Logo" style="height: 50px" />
                </router-link>
                <router-link class="navbar-brand" to="/inicio">Todo Videojuegos</router-link>

                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navegacion"
                    aria-controls="navegacion" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navegacion">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <router-link class="nav-link" to="/inicio">Inicio</router-link>
                        </li>
                        <li class="nav-item">
                            <router-link class="nav-link" to="/listadovideojuegos">Videojuegos</router-link>
                        </li>
                        <li v-if="esRolAdmin" class="nav-item">
                            <router-link class="nav-link" to="/administrador">*Administrador</router-link>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user"></i>
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                                <li>
                                    <router-link class="dropdown-item" to="/datospersonales">Datos
                                        personales</router-link>
                                </li>
                                <li>
                                    <a class="dropdown-item btn btn-primary" href="#"
                                        @click.prevent="eliminarSesion">Cerrar Sesión</a>
                                </li>
                                <li v-if="!esRolAdmin">
                                    <a class="dropdown-item btn btn-primary" href="#" data-bs-toggle="modal"
                                        data-bs-target="#deleteAccountModal">
                                        Eliminar Cuenta
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li class="nav-item">
                            <router-link class="nav-link" to="/carrito">
                                <i class="fa-solid fa-cart-shopping"></i>
                            </router-link>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Modal de confirmación de borrado -->
        <div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-labelledby="exampleModalLabel"
            aria-hidden="true" ref="modal">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Confirmación de Borrado</h5>
                    </div>
                    <div class="modal-body">
                        ¿Estás seguro de que deseas borrar tu cuenta? Esta acción no se puede deshacer.
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-danger" @click="borrarCuenta">Borrar Cuenta</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "MenuComponent",
    data() {
        return {
            esRolAdmin: false, // Puedes vincular esto con Vuex o props si lo necesitas
        };
    },
    methods: {
        eliminarSesion() {
            console.log("Sesión cerrada");
            // Aquí iría la lógica para cerrar sesión (limpiar token, redirigir, etc.)
        },
    },
};
</script>