<script setup>
import Modal from './Modal.vue';
import SecondaryButton from './SecondaryButton.vue';
import DangerButton from './DangerButton.vue';

const emit = defineEmits(['close', 'confirm']);

defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: 'Confirmar Acción',
    },
    message: {
        type: String,
        default: '¿Estás seguro de que quieres realizar esta acción? Esta operación no se puede deshacer.',
    },
});

const closeModal = () => {
    emit('close');
};

const confirmAction = () => {
    emit('confirm');
};
</script>

<template>
    <Modal :show="show" @close="closeModal">
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900">
                {{ title }}
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                {{ message }}
            </p>

            <div class="mt-6 flex justify-end">
                <SecondaryButton @click="closeModal"> Cancelar </SecondaryButton>

                <DangerButton class="ms-3" @click="confirmAction">
                    Confirmar
                </DangerButton>
            </div>
        </div>
    </Modal>
</template>