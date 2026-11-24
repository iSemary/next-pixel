<script setup lang="ts">
import { Sparkles, Wand2 } from '@lucide/vue';
</script>

<template>
    <div
        class="flex flex-1 flex-col items-center justify-center rounded-2xl border border-dashed border-border/80 bg-card/40 p-8 text-center sm:p-16 dark:bg-card/60"
    >
        <div
            class="relative mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-muted/60 text-muted-foreground ring-1 ring-border/80 dark:bg-card/80"
        >
            <Wand2 class="h-8 w-8 stroke-[1.5] text-muted-foreground" />
            <div
                class="absolute -top-1 -right-1 flex h-6 w-6 items-center justify-center rounded-full bg-primary/20 text-primary"
            >
                <Sparkles class="h-3.5 w-3.5" />
            </div>
        </div>

        <h3 class="font-bricolage text-base font-semibold text-foreground">
            No generations yet
        </h3>

        <p
            class="mt-1.5 max-w-sm text-xs leading-relaxed text-muted-foreground"
        >
            Configure your prompt, size, and quality settings on the left to see
            live pricing estimation. Your generated creations and live status
            will appear here.
        </p>
    </div>
</template>
