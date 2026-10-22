<script setup>
import { formatDateTime } from '../lib/tickets';

// isOverdue comes from the server (Ticket::isOverdue) so the rule lives in one place.
defineProps({
    dueAt: { type: String, default: null },
    isOverdue: { type: Boolean, default: false },
});
</script>

<template>
    <span class="text-sm" :class="isOverdue ? 'font-medium text-rose-600' : 'text-slate-600'">
        {{ formatDateTime(dueAt) }}
        <span v-if="isOverdue" class="ml-1 rounded bg-rose-600 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-white">Overdue</span>
    </span>
</template>
