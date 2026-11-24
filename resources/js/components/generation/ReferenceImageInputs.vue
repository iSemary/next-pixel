<script setup lang="ts">
import { computed } from 'vue';
import { Plus, Trash2, Image as ImageIcon, AlertCircle } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';

const props = withDefaults(
    defineProps<{
        modelValue: string[];
        maxImages?: number;
    }>(),
    {
        modelValue: () => [],
        maxImages: 4,
    },
);

const emit = defineEmits<{
    (e: 'update:modelValue', value: string[]): void;
}>();

const canAddMore = computed(() => props.modelValue.length < props.maxImages);

const addUrlField = () => {
    if (canAddMore.value) {
        emit('update:modelValue', [...props.modelValue, '']);
    }
};

const updateUrl = (index: number, value: string) => {
    const updated = [...props.modelValue];
    updated[index] = value;
    emit('update:modelValue', updated);
};

const removeUrlField = (index: number) => {
    const updated = props.modelValue.filter((_, i) => i !== index);
    emit('update:modelValue', updated);
};

const isInvalidUrl = (url: string): boolean => {
    const trimmed = url.trim();
    if (!trimmed) {
        return false;
    }
    try {
        const parsed = new URL(trimmed);
        return !['http:', 'https:'].includes(parsed.protocol);
    } catch {
        return true;
    }
};
</script>

<template>
    <div class="space-y-2">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-1.5">
                <Label
                    class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                >
                    Reference Images
                </Label>
                <span class="text-[11px] text-muted-foreground/80"
                    >(Optional)</span
                >
            </div>
            <span class="font-mono text-[11px] text-muted-foreground">
                {{ modelValue.length }}/{{ maxImages }}
            </span>
        </div>

        <p class="text-xs text-muted-foreground">
            Add up to 4 public image URLs to guide composition or style (+765
            tokens each).
        </p>

        <!-- URL fields list -->
        <div v-if="modelValue.length > 0" class="space-y-2 pt-1">
            <div
                v-for="(url, index) in modelValue"
                :key="index"
                class="space-y-1"
            >
                <div class="flex items-center gap-2">
                    <div class="relative flex-1">
                        <div
                            class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2.5 text-muted-foreground"
                        >
                            <ImageIcon class="h-3.5 w-3.5" />
                        </div>
                        <input
                            :value="url"
                            type="url"
                            placeholder="https://example.com/reference-image.jpg"
                            class="w-full rounded-md border border-input bg-background/50 py-1.5 pr-3 pl-8 text-xs placeholder:text-muted-foreground/50 focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none dark:bg-card/40"
                            :class="{
                                'border-destructive focus:border-destructive focus:ring-destructive':
                                    isInvalidUrl(url),
                            }"
                            @input="
                                (e) =>
                                    updateUrl(
                                        index,
                                        (e.target as HTMLInputElement).value,
                                    )
                            "
                        />
                    </div>
                    <Button
                        variant="ghost"
                        size="icon"
                        type="button"
                        class="h-8 w-8 text-muted-foreground hover:bg-destructive/10 hover:text-destructive"
                        title="Remove image URL"
                        @click="removeUrlField(index)"
                    >
                        <Trash2 class="h-3.5 w-3.5" />
                        <span class="sr-only">Remove</span>
                    </Button>
                </div>

                <!-- Inline URL Error -->
                <p
                    v-if="isInvalidUrl(url)"
                    class="flex items-center gap-1 text-[11px] text-destructive"
                >
                    <AlertCircle class="h-3 w-3" />
                    <span>Please enter a valid HTTP or HTTPS image URL.</span>
                </p>
            </div>
        </div>

        <!-- Add URL Button -->
        <Button
            v-if="canAddMore"
            variant="outline"
            size="sm"
            type="button"
            class="h-8 w-full border-dashed border-border/80 text-xs font-normal text-muted-foreground hover:border-primary hover:text-foreground"
            @click="addUrlField"
        >
            <Plus class="mr-1.5 h-3.5 w-3.5" />
            Add Reference Image URL
        </Button>
    </div>
</template>
