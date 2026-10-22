<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import TicketForm from '../../components/TicketForm.vue';

defineProps({ agents: { type: Array, required: true } });

const form = useForm({
    customer_id: null,
    subject: '',
    body: '',
    priority: 'normal',
    assignee_id: null,
});
</script>

<template>
    <Head title="New ticket" />
    <h1 class="mb-4 text-2xl font-semibold">New ticket</h1>
    <TicketForm :form="form" :agents="agents" submit-label="Create ticket" @submit="form.post('/tickets')" />
</template>
