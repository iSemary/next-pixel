<script setup lang="ts">
import { Zap, Sparkles, Flame } from '@lucide/vue';
import { Label } from '@/components/ui/label';
import type { ImageQualityOption } from '@/types/generation';

const props = defineProps<{
    modelValue: string;
    options: ImageQualityOption[];
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
}>();

const getQualityIcon = (id: string) => {
    switch (id) {
        case 'low':
            return Zap;
        case 'medium':
            return Sparkles;
        case 'high':
            return Flame;
        default:
            return Sparkles;
    }
};
</script>

<template>
    <div class="space-y-2">
        <div class="flex items-center justify-between">
            <Label
                class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
            >
                Quality
            </Label>
            <span
                class="text-[11px] font-medium text-muted-foreground capitalize"
            >
                {{ modelValue }} quality
            </span>
        </div>

        <div class="grid grid-cols-3 gap-2">
            <button
                v-for="option in options"
                :key="option.id"
                type="button"
                class="group relative flex flex-col items-center justify-center rounded-lg border p-2.5 text-center transition-all focus:ring-2 focus:ring-primary/40 focus:outline-none"
                :class="[
                    modelValue === option.value
                        ? 'border-primary bg-primary/10 text-foreground ring-1 ring-primary/60 dark:bg-primary/15'
                        : 'border-border/80 bg-background/50 text-muted-foreground hover:border-border hover:bg-muted/50 dark:bg-card/40',
                ]"
                @click="emit('update:modelValue', option.value)"
            >
                <div class="mb-1.5 flex h-6 items-center justify-center">
                    <component
                        :is="getQualityIcon(option.id)"
                        class="h-4 w-4 transition-transform group-hover:scale-110"
                        :class="
                            modelValue === option.value
                                ? 'text-primary'
                                : 'text-muted-foreground'
                        "
                    />
                </div>

                <span class="text-xs leading-none font-medium text-foreground">
                    {{ option.name }}
                </span>

                <span
                    class="mt-1 line-clamp-1 text-[10px] text-muted-foreground"
                    :title="option.description"
                >
                    {{
                        option.id === 'low'
                            ? 'Draft'
                            : option.id === 'medium'
                              ? 'Standard'
                              : 'HD'
                    }}
                </span>
            </button>
        </div>
    </div>
</template>
