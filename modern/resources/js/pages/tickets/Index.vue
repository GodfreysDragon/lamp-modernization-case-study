<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive, watch } from 'vue';
import DueDate from '../../components/DueDate.vue';
import Pagination from '../../components/Pagination.vue';
import PriorityLabel from '../../components/PriorityLabel.vue';
import StatusSelect from '../../components/StatusSelect.vue';
import { statuses } from '../../lib/tickets';

const props = defineProps({
    tickets: { type: Object, required: true },
    filters: { type: Object, required: true },
    agents: { type: Array, required: true },
});

const filters = reactive({
    q: props.filters.q ?? '',
    status: props.filters.status ?? '',
    assignee: props.filters.assignee ?? '',
});

// Filters update the URL as you type, so filtered views can be bookmarked and shared.
let timer;
watch(filters, () => {
    clearTimeout(timer);
    timer = setTimeout(() => {
        const query = Object.fromEntries(Object.entries(filters).filter(([, v]) => v !== '' && v !== null));
        router.get('/tickets', query, { preserveState: true, replace: true });
    }, 300);
});
</script>

<template>
    <Head title="Tickets" />
    <div class="mb-4 flex items-center justify-between">
        <h1 class="text-2xl font-semibold">Tickets</h1>
        <Link href="/tickets/create" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-500">New ticket</Link>
    </div>

    <div class="mb-4 flex flex-wrap gap-3">
        <input v-model="filters.q" type="search" placeholder="Search subject or description…" aria-label="Search" class="min-w-64 flex-1 rounded-md border border-slate-300 px-3 py-2 text-sm" />
        <select v-model="filters.status" aria-label="Status" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
            <option value="">Any status</option>
            <option v-for="(meta, value) in statuses" :key="value" :value="value">{{ meta.label }}</option>
        </select>
        <select v-model="filters.assignee" aria-label="Assignee" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
            <option value="">Anyone</option>
            <option v-for="agent in agents" :key="agent.id" :value="agent.id">{{ agent.name }}</option>
        </select>
    </div>

    <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-2">#</th>
                    <th class="px-4 py-2">Subject</th>
                    <th class="px-4 py-2">Customer</th>
                    <th class="px-4 py-2">Assigned</th>
                    <th class="px-4 py-2">Priority</th>
                    <th class="px-4 py-2">Due</th>
                    <th class="px-4 py-2">Status</th>
                    <th class="px-4 py-2 text-right">Comments</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <tr v-for="ticket in tickets.data" :key="ticket.id" :class="{ 'bg-rose-50/50': ticket.is_overdue }">
                    <td class="px-4 py-2 text-slate-500">{{ ticket.id }}</td>
                    <td class="max-w-xs truncate px-4 py-2">
                        <Link :href="`/tickets/${ticket.id}`" class="font-medium text-indigo-700 hover:underline">{{ ticket.subject }}</Link>
                    </td>
                    <td class="px-4 py-2">{{ ticket.customer?.label ?? '—' }}</td>
                    <td class="px-4 py-2" :class="{ 'italic text-slate-400': !ticket.assignee }">{{ ticket.assignee?.name ?? 'Unassigned' }}</td>
                    <td class="px-4 py-2"><PriorityLabel :priority="ticket.priority" /></td>
                    <td class="px-4 py-2"><DueDate :due-at="ticket.due_at" :is-overdue="ticket.is_overdue" /></td>
                    <td class="px-4 py-2"><StatusSelect :ticket-id="ticket.id" :status="ticket.status" /></td>
                    <td class="px-4 py-2 text-right">{{ ticket.comments_count }}</td>
                </tr>
                <tr v-if="tickets.data.length === 0">
                    <td colspan="8" class="px-4 py-6 text-center text-slate-500">No tickets match these filters.</td>
                </tr>
            </tbody>
        </table>
    </div>
    <Pagination :links="tickets.meta.links" />
</template>
