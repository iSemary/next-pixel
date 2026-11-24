<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';

defineOptions({
    layout: {
        title: 'Create an account',
        description: 'Enter your email and password to create your account',
    },
});

const form = useForm({
    email: '',
    password: '',
});

const submit = () => {
    form.post('/register', {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Create an account" />

    <form @submit.prevent="submit" class="flex flex-col gap-6 font-inter">
        <div class="grid gap-6">
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

            <div class="grid gap-2">
                <Label for="password">Password</Label>
                <PasswordInput
                    id="password"
                    v-model="form.password"
                    required
                    :tabindex="2"
                    autocomplete="new-password"
                    placeholder="Password"
                />
                <InputError :message="form.errors.password" />
            </div>

            <Button
                type="submit"
                class="mt-2 w-full bg-[#A3E600] font-semibold text-[#171717] hover:bg-[#b2f500]"
                :tabindex="3"
                :disabled="form.processing"
                data-test="register-user-button"
            >
                <Spinner v-if="form.processing" class="mr-2" />
                Create account
            </Button>
        </div>

        <div class="text-center text-sm text-muted-foreground">
            Already have an account?
            <TextLink
                :href="login()"
                class="underline underline-offset-4"
                :tabindex="4"
            >
                Log in
            </TextLink>
        </div>
    </form>
</template>
