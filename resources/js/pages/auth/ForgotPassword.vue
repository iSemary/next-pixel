<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthCardLayout from '@/layouts/auth/AuthCardLayout.vue';
import { login } from '@/routes';

defineProps<{
    status?: string;
}>();

const form = useForm({
    email: '',
});

const submit = () => {
    form.post('/forgot-password');
};
</script>

<template>
    <AuthCardLayout
        title="Forgot your password?"
        description="Enter your email address and we will email you a password reset link."
    >
        <Head title="Forgot Password" />

        <div
            v-if="status"
            class="mb-4 rounded-md bg-emerald-500/15 p-3 text-center text-sm font-medium text-emerald-800 dark:text-emerald-300"
        >
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="flex flex-col gap-6 font-inter">
            <div class="grid gap-2">
                <Label for="email">Email address</Label>
                <Input
                    id="email"
                    v-model="form.email"
                    type="email"
                    required
                    autofocus
                    :tabindex="1"
                    autocomplete="email"
                    placeholder="email@example.com"
                />
                <InputError :message="form.errors.email" />
            </div>

            <Button
                type="submit"
                class="w-full bg-[#A3E600] font-semibold text-[#171717] hover:bg-[#b2f500]"
                :tabindex="2"
                :disabled="form.processing"
            >
                <Spinner v-if="form.processing" class="mr-2" />
                Email password reset link
            </Button>

            <div class="text-center text-sm text-muted-foreground">
                Remember your password?
                <TextLink :href="login()" class="ml-1">Log in</TextLink>
            </div>
        </form>
    </AuthCardLayout>
</template>
