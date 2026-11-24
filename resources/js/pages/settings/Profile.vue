<script setup lang="ts">
import SettingsView from '@/components/SettingsView.vue';
import { edit } from '@/routes/profile';

withDefaults(
    defineProps<{
        mustVerifyEmail?: boolean;
        status?: string;
        passwordRules?: string;
    }>(),
    {
        mustVerifyEmail: false,
        status: '',
        passwordRules: '',
    },
);

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Settings',
                href: edit(),
            },
        ],
    },
});
</script>

<template>
    <SettingsView default-tab="general" :password-rules="passwordRules" />
</template>
