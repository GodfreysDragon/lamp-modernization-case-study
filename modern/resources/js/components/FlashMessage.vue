<script setup>
import { ref, watch } from 'vue';

const props = defineProps({ message: { type: String, default: null } });

const visible = ref(!!props.message);
let timer;

watch(
    () => props.message,
    (message) => {
        visible.value = !!message;
        clearTimeout(timer);
        if (message) timer = setTimeout(() => (visible.value = false), 4000);
    },
    { immediate: true },
);
</script>

<template>
    <div
        v-if="visible && message"
        role="status"
        class="mb-4 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm text-emerald-800"
    >
        {{ message }}
    </div>
</template>
