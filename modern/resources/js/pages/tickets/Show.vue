<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import CommentForm from '../../components/CommentForm.vue';
import CommentThread from '../../components/CommentThread.vue';
import DueDate from '../../components/DueDate.vue';
import PriorityLabel from '../../components/PriorityLabel.vue';
import StatusSelect from '../../components/StatusSelect.vue';

const props = defineProps({
    ticket: { type: Object, required: true },
    can: { type: Object, required: true },
});

function destroy() {
    if (confirm(`Delete ticket #${props.ticket.id}?`)) {
        router.delete(`/tickets/${props.ticket.id}`);
    }
}
</script>

<template>
    <Head :title="`#${ticket.id} ${ticket.subject}`" />
    <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
        <div>
            <Link href="/tickets" class="text-sm text-slate-500 hover:underline">← Tickets</Link>
            <h1 class="mt-1 text-2xl font-semibold">
                <span class="text-slate-400">#{{ ticket.id }}</span> {{ ticket.subject }}
            </h1>
        </div>
        <div class="flex gap-2">
            <Link :href="`/tickets/${ticket.id}/edit`" class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm hover:bg-slate-50">Edit</Link>
            <button v-if="can.delete" type="button" class="rounded-md border border-rose-300 bg-white px-3 py-1.5 text-sm text-rose-700 hover:bg-rose-50" @click="destroy">Delete</button>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <section class="rounded-lg border border-slate-200 bg-white p-5">
                <p v-if="ticket.body" class="whitespace-pre-line text-sm">{{ ticket.body }}</p>
                <p v-else class="text-sm italic text-slate-400">No description.</p>
            </section>
            <section>
                <h2 class="mb-3 text-lg font-semibold">Comments</h2>
                <CommentThread :comments="ticket.comments" />
                <div class="mt-4">
                    <CommentForm :ticket-id="ticket.id" />
                </div>
            </section>
        </div>

        <aside class="h-fit space-y-3 rounded-lg border border-slate-200 bg-white p-5 text-sm">
            <div>
                <div class="text-xs uppercase tracking-wide text-slate-500">Status</div>
                <StatusSelect :ticket-id="ticket.id" :status="ticket.status" class="mt-1" />
            </div>
            <div>
                <div class="text-xs uppercase tracking-wide text-slate-500">Customer</div>
                <div>{{ ticket.customer?.label ?? '—' }}</div>
                <div v-if="ticket.customer?.email" class="text-slate-500">{{ ticket.customer.email }}</div>
            </div>
            <div>
                <div class="text-xs uppercase tracking-wide text-slate-500">Assigned to</div>
                <div>{{ ticket.assignee?.name ?? 'Unassigned' }}</div>
            </div>
            <div>
                <div class="text-xs uppercase tracking-wide text-slate-500">Priority</div>
                <PriorityLabel :priority="ticket.priority" />
            </div>
            <div>
                <div class="text-xs uppercase tracking-wide text-slate-500">Due</div>
                <DueDate :due-at="ticket.due_at" :is-overdue="ticket.is_overdue" />
            </div>
        </aside>
    </div>
</template>
