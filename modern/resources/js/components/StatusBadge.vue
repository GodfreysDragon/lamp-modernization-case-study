<script setup>
import { computed } from 'vue';
import { statuses } from '../lib/tickets';

const props = defineProps({ status: { type: String, required: true } });

const meta = computed(() => statuses[props.status] ?? { label: props.status, classes: 'bg-slate-100 text-slate-700 ring-slate-200' });
</script>

<template>
    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset" :class="meta.classes">
        {{ meta.label }}
    </span>
</template>
