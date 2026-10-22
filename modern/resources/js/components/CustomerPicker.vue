<script setup>
import { onBeforeUnmount, ref } from 'vue';

// Replaces the jQuery UI autocomplete plus its hidden #customer_id field.
const model = defineModel({ type: Number, default: null });
const props = defineProps({
    initialLabel: { type: String, default: '' },
    error: { type: String, default: null },
});

const query = ref(props.initialLabel);
const results = ref([]);
const open = ref(false);
let timer;
let controller;

function onInput() {
    model.value = null;
    clearTimeout(timer);
    if (query.value.trim().length < 2) {
        results.value = [];
        open.value = false;
        return;
    }
    timer = setTimeout(search, 250);
}

async function search() {
    controller?.abort();
    controller = new AbortController();
    try {
        const response = await fetch(`/customers/search?term=${encodeURIComponent(query.value)}`, {
            headers: { Accept: 'application/json' },
            signal: controller.signal,
        });
        results.value = await response.json();
        open.value = true;
    } catch (e) {
        if (e.name !== 'AbortError') throw e;
    }
}

function choose(result) {
    model.value = result.id;
    query.value = result.label;
    open.value = false;
}

onBeforeUnmount(() => {
    clearTimeout(timer);
    controller?.abort();
});
</script>

<template>
    <div class="relative">
        <input
            id="customer"
            v-model="query"
            type="text"
            autocomplete="off"
            role="combobox"
            :aria-expanded="open"
            placeholder="Start typing a name or company…"
            class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
            @input="onInput"
            @blur="open = false"
        />
        <ul
            v-if="open"
            role="listbox"
            class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md border border-slate-200 bg-white text-sm shadow-lg"
        >
            <li v-if="results.length === 0" class="px-3 py-2 text-slate-500">No matches</li>
            <li
                v-for="result in results"
                :key="result.id"
                role="option"
                class="cursor-pointer px-3 py-2 hover:bg-indigo-50"
                @mousedown.prevent="choose(result)"
            >
                {{ result.label }}
            </li>
        </ul>
        <p v-if="error" class="mt-1 text-sm text-rose-600">{{ error }}</p>
    </div>
</template>
