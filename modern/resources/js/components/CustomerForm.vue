<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    form: { type: Object, required: true },
    submitLabel: { type: String, default: 'Save' },
});
defineEmits(['submit']);

const fields = [
    { key: 'name', label: 'Name', type: 'text' },
    { key: 'company', label: 'Company', type: 'text' },
    { key: 'email', label: 'Email', type: 'email' },
    { key: 'phone', label: 'Phone', type: 'tel' },
];
</script>

<template>
    <form class="max-w-2xl space-y-5 rounded-lg border border-slate-200 bg-white p-6" @submit.prevent="$emit('submit')">
        <div class="grid gap-4 sm:grid-cols-2">
            <div v-for="field in fields" :key="field.key">
                <label :for="field.key" class="mb-1 block text-sm font-medium">{{ field.label }}</label>
                <input
                    :id="field.key"
                    v-model="form[field.key]"
                    :type="field.type"
                    class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
                />
                <p v-if="form.errors[field.key]" class="mt-1 text-sm text-rose-600">{{ form.errors[field.key] }}</p>
            </div>
        </div>
        <div>
            <label for="notes" class="mb-1 block text-sm font-medium">Notes</label>
            <textarea id="notes" v-model="form.notes" rows="3" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm" />
        </div>
        <div class="flex items-center gap-3">
            <button
                type="submit"
                :disabled="form.processing"
                class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500 disabled:opacity-50"
            >
                {{ submitLabel }}
            </button>
            <Link href="/customers" class="text-sm text-slate-600 hover:underline">Cancel</Link>
        </div>
    </form>
</template>
