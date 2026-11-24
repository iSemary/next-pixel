<script setup lang="ts">
import { Download, ExternalLink } from '@lucide/vue';
import { computed } from 'vue';
import {
    formatGenerationUsd,
    GENERATION_STATUS_META,
} from '@/components/generation/generationStatusMeta';
import type { GenerationItem } from '@/types/generation';

const props = defineProps<{
    generation: GenerationItem;
}>();

const emit = defineEmits<{
    (e: 'select', generation: GenerationItem): void;
}>();

const statusMeta = computed(
    () => GENERATION_STATUS_META[props.generation.status],
);

const costDisplay = computed(() => {
    const gen = props.generation;

    if (gen.status === 'failed') {
        return {
            primary: 'Free ($0)',
            secondary: formatGenerationUsd(gen.estimated_user_cost, 'Est. ~'),
            isFree: true,
        };
    }

    if (gen.status === 'succeeded' && gen.actual_user_cost !== null) {
        return {
            primary: formatGenerationUsd(gen.actual_user_cost) || '$0',
            secondary: formatGenerationUsd(gen.estimated_user_cost, 'Est. ~'),
            isFree: false,
        };
    }

    return {
        primary: formatGenerationUsd(gen.estimated_user_cost, '~') || '—',
        secondary: null,
        isFree: false,
    };
});
</script>

<template>
    <div
        role="button"
        tabindex="0"
        class="group relative flex cursor-pointer flex-col overflow-hidden rounded-xl border border-border/80 bg-background/60 p-4 text-left shadow-2xs transition-all hover:border-primary/40 hover:shadow-sm focus:ring-2 focus:ring-primary/40 focus:outline-none"
        @click="emit('select', generation)"
        @keydown.enter.prevent="emit('select', generation)"
        @keydown.space.prevent="emit('select', generation)"
    >
        <!-- Status & Cost Header -->
        <div class="flex items-center justify-between gap-2 pb-3">
            <span
                class="inline-flex items-center gap-1.5 rounded-md px-2 py-0.5 text-xs font-medium"
                :class="statusMeta.badgeClass"
            >
                <component
                    :is="statusMeta.icon"
                    class="h-3.5 w-3.5"
                    :class="{ 'animate-spin': statusMeta.spin }"
                />
                {{ statusMeta.label }}
            </span>

            <div class="flex flex-col items-end font-mono leading-tight">
                <span
                    class="text-[11px] font-semibold"
                    :class="
                        costDisplay.isFree
                            ? 'text-emerald-600 dark:text-emerald-400'
                            : 'text-foreground'
                    "
                >
                    {{ costDisplay.primary }}
                </span>
                <span
                    v-if="costDisplay.secondary"
                    class="text-[10px] text-muted-foreground line-through decoration-muted-foreground/50"
                >
                    {{ costDisplay.secondary }}
                </span>
            </div>
        </div>

        <!-- Image Preview / Placeholder -->
        <div
            class="relative flex aspect-square w-full items-center justify-center overflow-hidden rounded-lg bg-muted/40"
        >
            <img
                v-if="generation.image_url"
                :src="generation.image_url"
                :alt="generation.prompt"
                loading="lazy"
                class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
            />

            <div
                v-if="generation.image_url"
                class="pointer-events-none absolute inset-x-0 bottom-0 flex items-center justify-end gap-1.5 bg-gradient-to-t from-black/60 via-black/20 to-transparent p-2.5 opacity-0 transition-opacity group-hover:opacity-100"
            >
                <a
                    :href="generation.image_url"
                    target="_blank"
                    rel="noopener noreferrer"
                    download
                    class="pointer-events-auto inline-flex h-7 w-7 items-center justify-center rounded-md bg-background/90 text-foreground shadow-xs transition-colors hover:bg-primary hover:text-primary-foreground"
                    title="Download PNG"
                    @click.stop
                >
                    <Download class="h-3.5 w-3.5" />
                </a>
                <a
                    :href="generation.image_url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="pointer-events-auto inline-flex h-7 w-7 items-center justify-center rounded-md bg-background/90 text-foreground shadow-xs transition-colors hover:bg-primary hover:text-primary-foreground"
                    title="Open image in new tab"
                    @click.stop
                >
                    <ExternalLink class="h-3.5 w-3.5" />
                </a>
            </div>

            <div
                v-else
                class="flex flex-col items-center justify-center gap-2 p-4 text-center text-muted-foreground"
            >
                <div
                    class="relative flex h-12 w-12 items-center justify-center rounded-full bg-muted/80"
                >
                    <component
                        :is="statusMeta.icon"
                        class="h-6 w-6"
                        :class="[
                            statusMeta.previewIconClass,
                            { 'animate-spin': statusMeta.spin },
                        ]"
                    />
                </div>
                <p class="text-xs font-medium text-foreground">
                    {{ statusMeta.placeholderText }}
                </p>
                <p
                    v-if="
                        generation.status === 'failed' &&
                        generation.error_message
                    "
                    class="line-clamp-2 max-w-[200px] text-[11px] text-red-600 dark:text-red-300"
                >
                    {{ generation.error_message }}
                </p>
            </div>
        </div>

        <!-- Prompt & Settings -->
        <div class="space-y-1.5 pt-3">
            <p
                class="line-clamp-2 text-xs font-medium text-foreground"
                :title="generation.prompt"
            >
                {{ generation.prompt }}
            </p>
            <div
                class="flex items-center justify-between gap-2 text-[10px] text-muted-foreground"
            >
                <div class="flex items-center gap-1.5">
                    <span>{{ generation.size }}</span>
                    <span>•</span>
                    <span class="capitalize">{{ generation.quality }}</span>
                </div>
                <span
                    v-if="generation.actual_total_tokens !== null"
                    class="font-mono"
                >
                    {{ generation.actual_total_tokens.toLocaleString() }} tok
                </span>
                <span
                    v-else-if="generation.estimated_total_tokens_max !== null"
                    class="font-mono"
                >
                    ~{{
                        generation.estimated_total_tokens_max.toLocaleString()
                    }}
                    tok
                </span>
            </div>
        </div>
    </div>
</template>
