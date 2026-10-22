<script setup>
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { statuses } from '../lib/tickets';

// Replaces the jQuery $('.status-change').change(...) handler. The request is an
// authenticated, CSRF-protected PATCH instead of an open AJAX endpoint.
const props = defineProps({
    ticketId: { type: Number, required: true },
    status: { type: String, required: true },
});

const saving = ref(false);

function update(event) {
    router.patch(
        `/tickets/${props.ticketId}/status`,
        { status: event.target.value },
        {
            preserveScroll: true,
            onStart: () => (saving.value = true),
            onFinish: () => (saving.value = false),
        },
    );
}
</script>

<template>
    <select
        :value="status"
        :disabled="saving"
        :aria-label="`Status for ticket #${ticketId}`"
        class="rounded-md border border-slate-300 bg-white py-1 pl-2 pr-7 text-sm disabled:opacity-50"
        @change="update"
    >
        <option v-for="(meta, value) in statuses" :key="value" :value="value">{{ meta.label }}</option>
    </select>
</template>
