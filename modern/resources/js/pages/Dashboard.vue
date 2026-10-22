<script setup>
import { Head, Link } from '@inertiajs/vue3';
import DueDate from '../components/DueDate.vue';
import PriorityLabel from '../components/PriorityLabel.vue';
import StatusBadge from '../components/StatusBadge.vue';

defineProps({
    counts: { type: Object, required: true },
    overdueCount: { type: Number, required: true },
    tickets: { type: Array, required: true },
});
</script>

<template>
    <Head title="Dashboard" />
    <h1 class="mb-6 text-2xl font-semibold">Dashboard</h1>

    <div class="mb-8 grid grid-cols-2 gap-4 md:grid-cols-4">
        <Link
            v-for="(count, status) in counts"
            :key="status"
            :href="`/tickets?status=${status}`"
            class="rounded-lg border border-slate-200 bg-white p-4 hover:border-indigo-300"
        >
            <StatusBadge :status="status" />
            <div class="mt-2 text-3xl font-semibold">{{ count }}</div>
        </Link>
        <div class="rounded-lg border border-rose-200 bg-rose-50 p-4">
            <span class="text-xs font-medium uppercase tracking-wide text-rose-700">Overdue</span>
            <div class="mt-2 text-3xl font-semibold text-rose-700">{{ overdueCount }}</div>
        </div>
    </div>

    <h2 class="mb-3 text-lg font-semibold">My open tickets ({{ tickets.length }})</h2>
    <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-2">#</th>
                    <th class="px-4 py-2">Subject</th>
                    <th class="px-4 py-2">Customer</th>
                    <th class="px-4 py-2">Priority</th>
                    <th class="px-4 py-2">Due</th>
                    <th class="px-4 py-2">Comments</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <tr v-for="ticket in tickets" :key="ticket.id">
                    <td class="px-4 py-2 text-slate-500">{{ ticket.id }}</td>
                    <td class="px-4 py-2"><Link :href="`/tickets/${ticket.id}`" class="font-medium text-indigo-700 hover:underline">{{ ticket.subject }}</Link></td>
                    <td class="px-4 py-2">{{ ticket.customer?.label ?? '—' }}</td>
                    <td class="px-4 py-2"><PriorityLabel :priority="ticket.priority" /></td>
                    <td class="px-4 py-2"><DueDate :due-at="ticket.due_at" :is-overdue="ticket.is_overdue" /></td>
                    <td class="px-4 py-2">{{ ticket.comments_count }}</td>
                </tr>
                <tr v-if="tickets.length === 0">
                    <td colspan="6" class="px-4 py-6 text-center text-slate-500">Nothing assigned to you right now.</td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
