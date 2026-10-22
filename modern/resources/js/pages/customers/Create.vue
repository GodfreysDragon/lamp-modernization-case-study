<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import CustomerForm from '../../components/CustomerForm.vue';

const form = useForm({ name: '', company: '', email: '', phone: '', notes: '' });
</script>

<template>
    <Head title="Add customer" />
    <h1 class="mb-4 text-2xl font-semibold">Add customer</h1>
    <CustomerForm :form="form" submit-label="Add customer" @submit="form.post('/customers')" />
</template>
