<script setup lang="ts">
import { Square, RectangleHorizontal, RectangleVertical } from '@lucide/vue';
import { Label } from '@/components/ui/label';
import type { ImageSizeOption } from '@/types/generation';

const props = defineProps<{
    modelValue: string;
    options: ImageSizeOption[];
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
}>();

const getIcon = (aspectRatio: string) => {
    switch (aspectRatio) {
        case '3:2':
            return RectangleHorizontal;
        case '2:3':
            return RectangleVertical;
        case '1:1':
        default:
            return Square;
    }
};
</script>

<template>
    <div class="space-y-2">
        <div class="flex items-center justify-between">
            <Label
                class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
            >
                Image Size
            </Label>
            <span class="font-mono text-[11px] text-muted-foreground">
                {{ modelValue }}
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
                <!-- Aspect Ratio Visual Shape -->
                <div class="mb-1.5 flex h-7 items-center justify-center">
                    <component
                        :is="getIcon(option.aspectRatio)"
                        class="h-5 w-5 transition-transform group-hover:scale-105"
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

                <div
                    class="mt-1 flex items-center gap-1 text-[10px] text-muted-foreground"
                >
                    <span class="font-mono">{{ option.aspectRatio }}</span>
                </div>
            </button>
        </div>
    </div>
</template>
