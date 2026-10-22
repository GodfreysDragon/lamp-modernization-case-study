<script setup>
import { computed } from 'vue';
import { priorities } from '../lib/tickets';

const props = defineProps({ priority: { type: String, required: true } });

const meta = computed(() => priorities[props.priority] ?? { label: props.priority, classes: '' });
</script>

<template>
    <span class="text-sm" :class="meta.classes">{{ meta.label }}</span>
</template>
