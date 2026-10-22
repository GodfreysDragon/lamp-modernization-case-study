<script setup>
import { formatDateTime } from '../lib/tickets';

// The legacy page echoed comment bodies straight into the page with nl2br(), so a comment containing
// <script> ran for every agent who opened the ticket. {{ }} interpolation always escapes,
// and white-space: pre-line keeps the line breaks without needing v-html.
defineProps({ comments: { type: Array, required: true } });
</script>

<template>
    <p v-if="comments.length === 0" class="text-sm text-slate-500">No comments yet.</p>
    <ol v-else class="space-y-3">
        <li
            v-for="comment in comments"
            :key="comment.id"
            class="rounded-lg border p-3"
            :class="comment.is_internal ? 'border-amber-200 bg-amber-50' : 'border-slate-200 bg-white'"
        >
            <div class="mb-1 flex items-center gap-2 text-xs text-slate-500">
                <span class="font-medium text-slate-800">{{ comment.author }}</span>
                <span>{{ formatDateTime(comment.created_at) }}</span>
                <span v-if="comment.is_internal" class="rounded bg-amber-200 px-1.5 py-0.5 text-amber-900">Internal</span>
            </div>
            <p class="whitespace-pre-line text-sm">{{ comment.body }}</p>
        </li>
    </ol>
</template>
