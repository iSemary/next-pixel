<script setup lang="ts">
import { Label } from '@/components/ui/label';

const COUNTS = [1, 2, 3, 4] as const;

withDefaults(
    defineProps<{
        modelValue?: number;
    }>(),
    {
        modelValue: 1,
    },
);

const emit = defineEmits<{
    (e: 'update:modelValue', value: number): void;
}>();
</script>

<template>
    <div class="space-y-1.5">
        <div class="flex items-center justify-between">
            <Label
                class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
            >
                Number of Generations
            </Label>
            <span class="font-mono text-[11px] text-muted-foreground">
                {{ modelValue }} {{ modelValue === 1 ? 'image' : 'images' }}
            </span>
        </div>

        <div class="grid grid-cols-4 gap-1.5">
            <button
                v-for="count in COUNTS"
                :key="count"
                type="button"
                class="flex h-9 cursor-pointer items-center justify-center rounded-lg border text-xs font-semibold transition-all focus:ring-2 focus:ring-primary/40 focus:outline-none"
                :class="[
                    modelValue === count
                        ? 'border-primary bg-primary text-primary-foreground shadow-xs ring-1 ring-primary/60'
                        : 'border-border/80 bg-background/50 text-muted-foreground hover:border-border hover:bg-muted/50 hover:text-foreground dark:bg-card/40',
                ]"
                @click="emit('update:modelValue', count)"
            >
                {{ count }}
            </button>
        </div>
    </div>
</template>
