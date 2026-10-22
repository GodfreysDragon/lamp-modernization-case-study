<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import Pagination from '../../components/Pagination.vue';

const props = defineProps({
    customers: { type: Object, required: true },
    filters: { type: Object, required: true },
    can: { type: Object, required: true },
});

const q = ref(props.filters.q ?? '');
let timer;
watch(q, (value) => {
    clearTimeout(timer);
    timer = setTimeout(() => router.get('/customers', value ? { q: value } : {}, { preserveState: true, replace: true }), 300);
});

function destroy(customer) {
    if (confirm(`Delete ${customer.name}? Their tickets are kept.`)) {
        router.delete(`/customers/${customer.id}`, { preserveScroll: true });
    }
}
</script>

<template>
    <Head title="Customers" />
    <div class="mb-4 flex items-center justify-between">
        <h1 class="text-2xl font-semibold">Customers</h1>
        <Link href="/customers/create" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-500">Add customer</Link>
    </div>

    <input v-model="q" type="search" placeholder="Search name or company…" aria-label="Search customers" class="mb-4 w-full max-w-md rounded-md border border-slate-300 px-3 py-2 text-sm" />

    <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-2">Name</th>
                    <th class="px-4 py-2">Company</th>
                    <th class="px-4 py-2">Email</th>
                    <th class="px-4 py-2">Phone</th>
                    <th class="px-4 py-2 text-right">Open tickets</th>
                    <th class="px-4 py-2"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <tr v-for="customer in customers.data" :key="customer.id">
                    <td class="px-4 py-2 font-medium">{{ customer.name }}</td>
                    <td class="px-4 py-2">{{ customer.company }}</td>
                    <td class="px-4 py-2">{{ customer.email }}</td>
                    <td class="px-4 py-2">{{ customer.phone }}</td>
                    <td class="px-4 py-2 text-right">{{ customer.open_tickets_count }}</td>
                    <td class="space-x-3 whitespace-nowrap px-4 py-2 text-right">
                        <Link :href="`/customers/${customer.id}/edit`" class="text-indigo-700 hover:underline">Edit</Link>
                        <button v-if="can.delete" type="button" class="text-rose-700 hover:underline" @click="destroy(customer)">Delete</button>
                    </td>
                </tr>
                <tr v-if="customers.data.length === 0">
                    <td colspan="6" class="px-4 py-6 text-center text-slate-500">No customers found.</td>
                </tr>
            </tbody>
        </table>
    </div>
    <Pagination :links="customers.meta.links" />
</template>
