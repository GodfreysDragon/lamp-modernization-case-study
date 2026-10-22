<script setup>
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({ username: '', password: '', remember: false });

function submit() {
    form.post('/login', { onFinish: () => form.reset('password') });
}
</script>

<template>
    <Head title="Log in" />
    <div class="flex min-h-screen items-center justify-center px-4">
        <form class="w-full max-w-sm space-y-4 rounded-xl border border-slate-200 bg-white p-8 shadow-sm" @submit.prevent="submit">
            <h1 class="text-xl font-semibold text-indigo-700">HelpDesk</h1>
            <div>
                <label for="username" class="mb-1 block text-sm font-medium">Username</label>
                <input id="username" v-model="form.username" type="text" autocomplete="username" autofocus class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm" />
                <p v-if="form.errors.username" class="mt-1 text-sm text-rose-600">{{ form.errors.username }}</p>
            </div>
            <div>
                <label for="password" class="mb-1 block text-sm font-medium">Password</label>
                <input id="password" v-model="form.password" type="password" autocomplete="current-password" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm" />
                <p v-if="form.errors.password" class="mt-1 text-sm text-rose-600">{{ form.errors.password }}</p>
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input v-model="form.remember" type="checkbox" class="rounded border-slate-300" /> Remember me
            </label>
            <button type="submit" :disabled="form.processing" class="w-full rounded-md bg-indigo-600 py-2 text-sm font-medium text-white hover:bg-indigo-500 disabled:opacity-50">
                Log in
            </button>
        </form>
    </div>
</template>
