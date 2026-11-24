<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import DeleteUser from '@/components/DeleteUser.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import type { User } from '@/types';

const props = withDefaults(
    defineProps<{
        defaultTab?: 'general' | 'security';
        passwordRules?: string;
    }>(),
    {
        defaultTab: 'general',
        passwordRules: '',
    },
);

const page = usePage();
const user = computed<User>(() => page.props.auth.user);

const activeTab = ref(props.defaultTab);

// Profile form
const profileForm = useForm({
    name: user.value?.name ?? '',
    email: user.value?.email ?? '',
});

const submitProfile = () => {
    profileForm.patch('/settings/profile', {
        preserveScroll: true,
    });
};

// Password form
const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    passwordForm.put('/settings/password', {
        preserveScroll: true,
        onSuccess: () => passwordForm.reset(),
        onError: () => passwordForm.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Settings" />

    <Tabs v-model="activeTab" class="space-y-6">
        <TabsList>
            <TabsTrigger value="general" class="cursor-pointer px-4">
                General
            </TabsTrigger>
            <TabsTrigger value="security" class="cursor-pointer px-4">
                Security
            </TabsTrigger>
        </TabsList>

        <!-- General Tab -->
        <TabsContent value="general" class="w-full space-y-8">
            <div class="space-y-6">
                <Heading
                    variant="small"
                    title="Profile"
                    description="Update your name and email address"
                />

                <form @submit.prevent="submitProfile" class="space-y-6">
                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input
                            id="name"
                            v-model="profileForm.name"
                            class="mt-1 block w-full"
                            required
                            autocomplete="name"
                            placeholder="Full name"
                        />
                        <InputError
                            class="mt-2"
                            :message="profileForm.errors.name"
                        />
                    </div>

                    <div class="grid gap-2">
                        <Label for="email">Email address</Label>
                        <Input
                            id="email"
                            v-model="profileForm.email"
                            type="email"
                            class="mt-1 block w-full"
                            required
                            autocomplete="username"
                            placeholder="Email address"
                        />
                        <InputError
                            class="mt-2"
                            :message="profileForm.errors.email"
                        />
                    </div>

                    <div class="flex items-center gap-4">
                        <Button
                            :disabled="profileForm.processing"
                            data-test="update-profile-button"
                        >
                            <Spinner
                                v-if="profileForm.processing"
                                class="mr-2"
                            />
                            Save
                        </Button>
                    </div>
                </form>

                <DeleteUser />
            </div>
        </TabsContent>

        <!-- Security Tab -->
        <TabsContent value="security" class="w-full space-y-6">
            <Heading
                variant="small"
                title="Update password"
                description="Ensure your account is using a long, random password to stay secure"
            />

            <form @submit.prevent="updatePassword" class="space-y-6">
                <div class="grid gap-2">
                    <Label for="current_password">Current password</Label>
                    <PasswordInput
                        id="current_password"
                        v-model="passwordForm.current_password"
                        class="mt-1 block w-full"
                        autocomplete="current-password"
                        placeholder="Current password"
                        required
                    />
                    <InputError
                        :message="passwordForm.errors.current_password"
                    />
                </div>

                <div class="grid gap-2">
                    <Label for="password">New password</Label>
                    <PasswordInput
                        id="password"
                        v-model="passwordForm.password"
                        class="mt-1 block w-full"
                        autocomplete="new-password"
                        placeholder="New password"
                        required
                    />
                    <InputError :message="passwordForm.errors.password" />
                </div>

                <div class="grid gap-2">
                    <Label for="password_confirmation">Confirm password</Label>
                    <PasswordInput
                        id="password_confirmation"
                        v-model="passwordForm.password_confirmation"
                        class="mt-1 block w-full"
                        autocomplete="new-password"
                        placeholder="Confirm password"
                        required
                    />
                    <InputError
                        :message="passwordForm.errors.password_confirmation"
                    />
                </div>

                <div class="flex items-center gap-4">
                    <Button
                        :disabled="passwordForm.processing"
                        data-test="update-password-button"
                    >
                        <Spinner v-if="passwordForm.processing" class="mr-2" />
                        Save
                    </Button>
                </div>
            </form>
        </TabsContent>
    </Tabs>
</template>
