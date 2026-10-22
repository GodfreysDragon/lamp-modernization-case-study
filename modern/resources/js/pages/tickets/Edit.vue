<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import TicketForm from '../../components/TicketForm.vue';

const props = defineProps({
    ticket: { type: Object, required: true },
    customer: { type: Object, default: null },
    agents: { type: Array, required: true },
});

const form = useForm({
    customer_id: props.ticket.customer_id,
    subject: props.ticket.subject,
    body: props.ticket.body ?? '',
    priority: props.ticket.priority,
    assignee_id: props.ticket.assignee_id,
});
</script>

<template>
    <Head :title="`Edit #${ticket.id}`" />
    <h1 class="mb-4 text-2xl font-semibold">Edit ticket #{{ ticket.id }}</h1>
    <TicketForm
        :form="form"
        :agents="agents"
        :customer-label="customer?.label ?? ''"
        :cancel-href="`/tickets/${ticket.id}`"
        submit-label="Save changes"
        @submit="form.put(`/tickets/${ticket.id}`)"
    />
</template>
