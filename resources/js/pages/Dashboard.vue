<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import GenerationForm from '@/components/generation/GenerationForm.vue';
import GenerationGallery from '@/components/generation/GenerationGallery.vue';
import { dashboard } from '@/routes';
import type { ImageQualityOption, ImageSizeOption } from '@/types/generation';

withDefaults(
    defineProps<{
        metrics?: Record<string, unknown>;
        supportedSizes: ImageSizeOption[];
        supportedQualities: ImageQualityOption[];
        testingKeywords?: string[];
    }>(),
    {
        metrics: () => ({}),
        testingKeywords: () => [],
    },
);

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Image Studio',
                href: dashboard(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Image Studio" />

    <div class="flex flex-1 flex-col p-4 md:p-6">
        <div
            class="flex w-full flex-1 flex-col gap-6 lg:flex-row lg:items-start"
        >
            <!-- Left Column: Image Generation Form & Live Pricing (380-420px) -->
            <div class="w-full lg:w-[380px] lg:shrink-0 xl:w-[420px]">
                <GenerationForm
                    :supported-sizes="supportedSizes"
                    :supported-qualities="supportedQualities"
                    :testing-keywords="testingKeywords"
                />
            </div>

            <!-- Right Column: Generation Gallery Workspace (occupies remaining width) -->
            <div class="flex min-w-0 flex-1 flex-col">
                <GenerationGallery />
            </div>
        </div>
    </div>
</template>
