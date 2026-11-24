<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowUpRight, Image as ImageIcon } from '@lucide/vue';
import GenerationEmptyState from '@/components/generation/GenerationEmptyState.vue';
import { library } from '@/routes';
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
                <div>
                    <h2
                        class="font-bricolage text-base font-semibold tracking-tight text-foreground"
                    >
                        Generation Gallery
                    </h2>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <Link
                    :href="library()"
                    class="inline-flex items-center gap-1 text-xs font-medium text-muted-foreground transition-colors hover:text-primary"
                >
                    <span>View library</span>
                    <ArrowUpRight class="h-3.5 w-3.5" />
                </Link>

                <span
                    class="rounded-full bg-muted/80 px-2.5 py-0.5 text-[11px] font-medium text-muted-foreground"
                >
                    0 generations
                </span>
            </div>
        </div>

        <!-- Gallery Body (Empty State for Step 06) -->
        <GenerationEmptyState />
    </div>
</template>
