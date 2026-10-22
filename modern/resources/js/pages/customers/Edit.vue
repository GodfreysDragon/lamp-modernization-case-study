<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import CustomerForm from '../../components/CustomerForm.vue';

const props = defineProps({ customer: { type: Object, required: true } });

const form = useForm({
    name: props.customer.name,
    company: props.customer.company ?? '',
    email: props.customer.email ?? '',
    phone: props.customer.phone ?? '',
    notes: props.customer.notes ?? '',
});
</script>

<template>
    <Head :title="`Edit ${customer.name}`" />
    <h1 class="mb-4 text-2xl font-semibold">Edit {{ customer.name }}</h1>
    <CustomerForm :form="form" submit-label="Save changes" @submit="form.put(`/customers/${customer.id}`)" />
</template>
