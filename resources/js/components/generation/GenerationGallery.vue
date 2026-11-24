<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowUpRight, Image as ImageIcon, Loader2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import GenerationCard from '@/components/generation/GenerationCard.vue';
import GenerationDetailDialog from '@/components/generation/GenerationDetailDialog.vue';
import GenerationEmptyState from '@/components/generation/GenerationEmptyState.vue';
import { useGenerationPolling } from '@/composables/useGenerationPolling';
import { useGenerationStudio } from '@/composables/useGenerationStudio';
import { dashboard } from '@/routes';
import type { GenerationItem } from '@/types/generation';

const { submittedGenerations, mergeUpdatedGenerations } = useGenerationStudio();

const selectedGenerationId = ref<number | null>(null);
const isDetailOpen = ref(false);

const { isPolling } = useGenerationPolling(
    () => submittedGenerations.value,
    (updated) => {
        mergeUpdatedGenerations(updated);
    },
);

const generationCount = computed(() => submittedGenerations.value.length);

const selectedGeneration = computed<GenerationItem | null>(() => {
    if (selectedGenerationId.value === null) {
        return null;
    }

    return (
        submittedGenerations.value.find(
            (g) => g.id === selectedGenerationId.value,
        ) ?? null
    );
});

function openDetail(gen: GenerationItem): void {
    selectedGenerationId.value = gen.id;
    isDetailOpen.value = true;
}
</script>

<template>
    <div
        class="flex flex-1 flex-col gap-4 rounded-2xl border border-border/80 bg-card p-5 shadow-xs dark:bg-card/90"
    >
        <!-- Gallery Header -->
        <div
            class="flex items-center justify-between border-b border-border/60 pb-3"
        >
            <div class="flex items-center gap-2">
                <div
                    class="flex h-7 w-7 items-center justify-center rounded-lg bg-muted text-muted-foreground"
                >
                    <ImageIcon class="h-4 w-4" />
                </div>
                <div class="flex items-center gap-2.5">
                    <h2
                        class="font-bricolage text-base font-semibold tracking-tight text-foreground"
                    >
                        Generation Gallery
                    </h2>
                    <span
                        v-if="isPolling"
                        class="inline-flex items-center gap-1 rounded-full bg-primary/10 px-2 py-0.5 text-[11px] font-medium text-primary"
                    >
                        <Loader2 class="h-3 w-3 animate-spin" />
                        Live
                    </span>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <Link
                    :href="dashboard()"
                    class="inline-flex items-center gap-1 text-xs font-medium text-muted-foreground transition-colors hover:text-primary"
                >
                    <span>View all</span>
                    <ArrowUpRight class="h-3.5 w-3.5" />
                </Link>

                <span
                    class="rounded-full bg-muted/80 px-2.5 py-0.5 text-[11px] font-medium text-muted-foreground"
                >
                    {{ generationCount }} generation{{
                        generationCount === 1 ? '' : 's'
                    }}
                </span>
            </div>
        </div>

        <!-- Gallery Body -->
        <div
            v-if="generationCount > 0"
            class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3"
        >
            <GenerationCard
                v-for="gen in submittedGenerations"
                :key="gen.id"
                :generation="gen"
                @select="openDetail"
            />
        </div>

        <!-- Empty State -->
        <GenerationEmptyState v-else />

        <GenerationDetailDialog
            v-model:open="isDetailOpen"
            :generation="selectedGeneration"
        />
    </div>
</template>
