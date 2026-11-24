<script setup lang="ts">
import SettingsView from '@/components/SettingsView.vue';
import { edit } from '@/routes/security';

withDefaults(
    defineProps<{
        passwordRules?: string;
    }>(),
    {
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
    <SettingsView default-tab="security" :password-rules="passwordRules" />
</template>
