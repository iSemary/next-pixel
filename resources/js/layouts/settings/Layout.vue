<script setup lang="ts">
import Heading from '@/components/Heading.vue';
</script>

<template>
    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="w-full space-y-6">
            <Heading
                title="Settings"
                description="Manage your profile and account settings"
            />

            <slot />
        </div>
    </div>
</template>
