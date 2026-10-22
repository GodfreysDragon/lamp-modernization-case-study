<script setup>
import { Link } from '@inertiajs/vue3';

// `links` is the meta.links array from a Laravel paginated resource.
defineProps({ links: { type: Array, required: true } });

function label(text) {
    return text.replace('&laquo;', '‹').replace('&raquo;', '›');
}
</script>

<template>
    <nav v-if="links.length > 3" class="mt-4 flex flex-wrap gap-1" aria-label="Pagination">
        <template v-for="(link, i) in links" :key="i">
            <Link
                v-if="link.url"
                :href="link.url"
                preserve-scroll
                class="rounded-md border px-3 py-1 text-sm"
                :class="link.active ? 'border-indigo-600 bg-indigo-600 text-white' : 'border-slate-300 bg-white hover:bg-slate-50'"
                :aria-current="link.active ? 'page' : undefined"
            >
                {{ label(link.label) }}
            </Link>
            <span v-else class="rounded-md border border-slate-200 px-3 py-1 text-sm text-slate-400">{{ label(link.label) }}</span>
        </template>
    </nav>
</template>
