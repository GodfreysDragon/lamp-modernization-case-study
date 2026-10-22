<script setup>
import { Link } from '@inertiajs/vue3';
import { priorities } from '../lib/tickets';
import CustomerPicker from './CustomerPicker.vue';

// Shared by tickets/Create and tickets/Edit. `form` is an Inertia useForm() object.
defineProps({
    form: { type: Object, required: true },
    agents: { type: Array, required: true },
    customerLabel: { type: String, default: '' },
    submitLabel: { type: String, default: 'Save' },
    cancelHref: { type: String, default: '/tickets' },
});
defineEmits(['submit']);
</script>

<template>
    <form class="max-w-2xl space-y-5 rounded-lg border border-slate-200 bg-white p-6" @submit.prevent="$emit('submit')">
        <div>
            <label for="customer" class="mb-1 block text-sm font-medium">Customer</label>
            <CustomerPicker v-model="form.customer_id" :initial-label="customerLabel" :error="form.errors.customer_id" />
        </div>

        <div>
            <label for="subject" class="mb-1 block text-sm font-medium">Subject</label>
            <input id="subject" v-model="form.subject" type="text" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm" />
            <p v-if="form.errors.subject" class="mt-1 text-sm text-rose-600">{{ form.errors.subject }}</p>
        </div>

        <div>
            <label for="body" class="mb-1 block text-sm font-medium">Description</label>
            <textarea id="body" v-model="form.body" rows="6" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm" />
            <p v-if="form.errors.body" class="mt-1 text-sm text-rose-600">{{ form.errors.body }}</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="priority" class="mb-1 block text-sm font-medium">Priority</label>
                <select id="priority" v-model="form.priority" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                    <option v-for="(meta, value) in priorities" :key="value" :value="value">{{ meta.label }}</option>
                </select>
                <p class="mt-1 text-xs text-slate-500">The due date is set from the priority's SLA.</p>
            </div>
            <div>
                <label for="assignee" class="mb-1 block text-sm font-medium">Assign to</label>
                <select id="assignee" v-model="form.assignee_id" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                    <option :value="null">Nobody</option>
                    <option v-for="agent in agents" :key="agent.id" :value="agent.id">{{ agent.name }}</option>
                </select>
                <p v-if="form.errors.assignee_id" class="mt-1 text-sm text-rose-600">{{ form.errors.assignee_id }}</p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button
                type="submit"
                :disabled="form.processing"
                class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500 disabled:opacity-50"
            >
                {{ submitLabel }}
            </button>
            <Link :href="cancelHref" class="text-sm text-slate-600 hover:underline">Cancel</Link>
        </div>
    </form>
</template>
