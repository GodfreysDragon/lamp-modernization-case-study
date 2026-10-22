<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import FlashMessage from '../components/FlashMessage.vue';

const page = usePage();
const user = computed(() => page.props.auth.user);

const nav = [
    { label: 'Dashboard', href: '/', match: (url) => url === '/' },
    { label: 'Tickets', href: '/tickets', match: (url) => url.startsWith('/tickets') },
    { label: 'Customers', href: '/customers', match: (url) => url.startsWith('/customers') },
];
</script>

<template>
    <div class="min-h-screen">
        <header class="border-b border-slate-200 bg-white">
            <div class="mx-auto flex max-w-6xl flex-wrap items-center gap-x-8 gap-y-2 px-4 py-3">
                <Link href="/" class="text-lg font-semibold text-indigo-700">HelpDesk</Link>
                <nav class="flex gap-1 text-sm">
                    <Link
                        v-for="item in nav"
                        :key="item.href"
                        :href="item.href"
                        class="rounded-md px-3 py-1.5"
                        :class="item.match($page.url.split('?')[0]) ? 'bg-indigo-50 text-indigo-700 font-medium' : 'text-slate-600 hover:bg-slate-100'"
                    >
                        {{ item.label }}
                    </Link>
                </nav>
                <div class="ml-auto flex items-center gap-3 text-sm text-slate-600">
                    <span>{{ user.name }}<span v-if="user.is_admin" class="ml-1 text-xs text-slate-400">(admin)</span></span>
                    <Link href="/logout" method="post" as="button" class="rounded-md px-2 py-1 hover:bg-slate-100">Log out</Link>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-6xl px-4 py-6">
            <FlashMessage :message="$page.props.flash.success" />
            <slot />
        </main>
    </div>
</template>
