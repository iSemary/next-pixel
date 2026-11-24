<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

type AuthMode = 'login' | 'register' | 'forgot-password';

const props = withDefaults(
    defineProps<{
        open: boolean;
        initialMode?: AuthMode;
    }>(),
    {
        open: false,
        initialMode: 'login',
    },
);

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
}>();

const mode = ref<AuthMode>(props.initialMode);
const forgotPasswordStatus = ref<string | null>(null);

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) {
            mode.value = props.initialMode;
            forgotPasswordStatus.value = null;
        }
    },
);

watch(
    () => props.initialMode,
    (newMode) => {
        mode.value = newMode;
        forgotPasswordStatus.value = null;
    },
);

const loginForm = useForm({
    email: '',
    password: '',
    remember: false,
});

const registerForm = useForm({
    email: '',
    password: '',
});

const forgotPasswordForm = useForm({
    email: '',
});

const submitLogin = () => {
    loginForm.post('/login', {
        onSuccess: () => {
            emit('update:open', false);
        },
        onFinish: () => loginForm.reset('password'),
    });
};

const submitRegister = () => {
    registerForm.post('/register', {
        onSuccess: () => {
            emit('update:open', false);
        },
        onFinish: () => registerForm.reset('password'),
    });
};

const submitForgotPassword = () => {
    forgotPasswordStatus.value = null;
    forgotPasswordForm.post('/forgot-password', {
        onSuccess: () => {
            forgotPasswordStatus.value =
                'We have emailed your password reset link.';
            forgotPasswordForm.reset();
        },
    });
};

const switchMode = (newMode: AuthMode) => {
    mode.value = newMode;
    loginForm.clearErrors();
    registerForm.clearErrors();
    forgotPasswordForm.clearErrors();
    forgotPasswordStatus.value = null;
};
</script>

<template>
    <Dialog
        :open="open"
        @update:open="(val: boolean) => emit('update:open', val)"
    >
        <DialogContent class="font-inter sm:max-w-[425px]">
            <!-- Header for Login -->
            <DialogHeader v-if="mode === 'login'" class="space-y-1.5 text-left">
                <DialogTitle
                    class="font-inter text-xl font-semibold tracking-tight text-foreground"
                >
                    Log in to your account
                </DialogTitle>
                <DialogDescription
                    class="font-inter text-sm text-muted-foreground"
                >
                    Enter your email and password below to log in
                </DialogDescription>
            </DialogHeader>

            <!-- Header for Register -->
            <DialogHeader
                v-else-if="mode === 'register'"
                class="space-y-1.5 text-left"
            >
                <DialogTitle
                    class="font-inter text-xl font-semibold tracking-tight text-foreground"
                >
                    Create an account
                </DialogTitle>
                <DialogDescription
                    class="font-inter text-sm text-muted-foreground"
                >
                    Enter your email and password to create your account
                </DialogDescription>
            </DialogHeader>

            <!-- Header for Forgot Password -->
            <DialogHeader v-else class="space-y-1.5 text-left">
                <DialogTitle
                    class="font-inter text-xl font-semibold tracking-tight text-foreground"
                >
                    Forgot your password?
                </DialogTitle>
                <DialogDescription
                    class="font-inter text-sm text-muted-foreground"
                >
                    Enter your email address and we'll send you a password reset
                    link
                </DialogDescription>
            </DialogHeader>

            <!-- 1. Login Form -->
            <form
                v-if="mode === 'login'"
                @submit.prevent="submitLogin"
                class="flex flex-col gap-4 pt-2"
            >
                <div class="grid gap-2">
                    <Label for="modal-email" class="font-inter"
                        >Email address</Label
                    >
                    <Input
                        id="modal-email"
                        v-model="loginForm.email"
                        type="email"
                        required
                        autofocus
                        autocomplete="email"
                        placeholder="email@example.com"
                        class="font-inter"
                    />
                    <InputError :message="loginForm.errors.email" />
                </div>

                <div class="grid gap-2">
                    <div class="flex items-center justify-between">
                        <Label for="modal-password" class="font-inter"
                            >Password</Label
                        >
                        <button
                            type="button"
                            class="cursor-pointer font-inter text-xs text-muted-foreground underline underline-offset-4 transition-colors hover:text-primary"
                            @click="switchMode('forgot-password')"
                        >
                            Forgot password?
                        </button>
                    </div>
                    <PasswordInput
                        id="modal-password"
                        v-model="loginForm.password"
                        required
                        autocomplete="current-password"
                        placeholder="Password"
                        class="font-inter"
                    />
                    <InputError :message="loginForm.errors.password" />
                </div>

                <div class="flex items-center justify-between">
                    <Label
                        for="modal-remember"
                        class="flex cursor-pointer items-center space-x-2.5 font-inter text-sm"
                    >
                        <Checkbox
                            id="modal-remember"
                            :checked="loginForm.remember"
                            @update:checked="
                                (val: boolean) => (loginForm.remember = val)
                            "
                        />
                        <span class="font-inter text-muted-foreground"
                            >Remember me</span
                        >
                    </Label>
                </div>

                <Button
                    type="submit"
                    class="mt-2 w-full font-inter"
                    :disabled="loginForm.processing"
                >
                    <Spinner v-if="loginForm.processing" class="mr-2" />
                    Log in
                </Button>

                <div
                    class="pt-2 text-center font-inter text-sm text-muted-foreground"
                >
                    Don't have an account?
                    <button
                        type="button"
                        class="ml-1 cursor-pointer font-inter font-medium text-foreground underline underline-offset-4 transition-colors hover:text-primary"
                        @click="switchMode('register')"
                    >
                        Sign up
                    </button>
                </div>
            </form>

            <!-- 2. Register Form (Email and Password Only) -->
            <form
                v-else-if="mode === 'register'"
                @submit.prevent="submitRegister"
                class="flex flex-col gap-4 pt-2"
            >
                <div class="grid gap-2">
                    <Label for="modal-reg-email" class="font-inter"
                        >Email address</Label
                    >
                    <Input
                        id="modal-reg-email"
                        v-model="registerForm.email"
                        type="email"
                        required
                        autofocus
                        autocomplete="email"
                        placeholder="email@example.com"
                        class="font-inter"
                    />
                    <InputError :message="registerForm.errors.email" />
                </div>

                <div class="grid gap-2">
                    <Label for="modal-reg-password" class="font-inter"
                        >Password</Label
                    >
                    <PasswordInput
                        id="modal-reg-password"
                        v-model="registerForm.password"
                        required
                        autocomplete="new-password"
                        placeholder="Password"
                        class="font-inter"
                    />
                    <InputError :message="registerForm.errors.password" />
                </div>

                <Button
                    type="submit"
                    class="mt-2 w-full font-inter"
                    :disabled="registerForm.processing"
                >
                    <Spinner v-if="registerForm.processing" class="mr-2" />
                    Create account
                </Button>

                <div
                    class="pt-2 text-center font-inter text-sm text-muted-foreground"
                >
                    Already have an account?
                    <button
                        type="button"
                        class="ml-1 cursor-pointer font-inter font-medium text-foreground underline underline-offset-4 transition-colors hover:text-primary"
                        @click="switchMode('login')"
                    >
                        Log in
                    </button>
                </div>
            </form>

            <!-- 3. Forgot Password Form -->
            <form
                v-else
                @submit.prevent="submitForgotPassword"
                class="flex flex-col gap-4 pt-2"
            >
                <div
                    v-if="forgotPasswordStatus"
                    class="rounded-md bg-emerald-500/15 p-3 text-center text-sm font-medium text-emerald-800 dark:text-emerald-300"
                >
                    {{ forgotPasswordStatus }}
                </div>

                <div class="grid gap-2">
                    <Label for="modal-forgot-email" class="font-inter"
                        >Email address</Label
                    >
                    <Input
                        id="modal-forgot-email"
                        v-model="forgotPasswordForm.email"
                        type="email"
                        required
                        autofocus
                        autocomplete="email"
                        placeholder="email@example.com"
                        class="font-inter"
                    />
                    <InputError :message="forgotPasswordForm.errors.email" />
                </div>

                <Button
                    type="submit"
                    class="mt-2 w-full font-inter"
                    :disabled="forgotPasswordForm.processing"
                >
                    <Spinner
                        v-if="forgotPasswordForm.processing"
                        class="mr-2"
                    />
                    Email password reset link
                </Button>

                <div
                    class="pt-2 text-center font-inter text-sm text-muted-foreground"
                >
                    Remember your password?
                    <button
                        type="button"
                        class="ml-1 cursor-pointer font-inter font-medium text-foreground underline underline-offset-4 transition-colors hover:text-primary"
                        @click="switchMode('login')"
                    >
                        Log in
                    </button>
                </div>
            </form>
        </DialogContent>
    </Dialog>
</template>
