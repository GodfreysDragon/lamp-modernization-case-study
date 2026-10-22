<script setup>
import { useForm } from '@inertiajs/vue3';

const props = defineProps({ ticketId: { type: Number, required: true } });

const form = useForm({ body: '', is_internal: false });

function submit() {
    form.post(`/tickets/${props.ticketId}/comments`, {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}
</script>

<template>
    <form class="space-y-2" @submit.prevent="submit">
        <label for="comment-body" class="sr-only">Comment</label>
        <textarea
            id="comment-body"
            v-model="form.body"
            rows="4"
            class="w-full rounded-md border border-slate-300 p-2 text-sm"
            placeholder="Write a reply or internal note…"
        />
        <p v-if="form.errors.body" class="text-sm text-rose-600">{{ form.errors.body }}</p>
        <div class="flex items-center justify-between">
            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input v-model="form.is_internal" type="checkbox" class="rounded border-slate-300" />
                Internal note
            </label>
            <button
                type="submit"
                :disabled="form.processing"
                class="rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-500 disabled:opacity-50"
            >
                {{ form.processing ? 'Saving…' : 'Add comment' }}
            </button>
        </div>
    </form>
</template>
