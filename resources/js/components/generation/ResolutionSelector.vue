<script setup lang="ts">
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

export interface ResolutionOption {
    value: string;
    label: string;
    quality: 'low' | 'medium' | 'high';
}

const RESOLUTIONS: ResolutionOption[] = [
    { value: '1K', label: '1K', quality: 'low' },
    { value: '2K', label: '2K', quality: 'medium' },
    { value: '4K', label: '4K', quality: 'high' },
];

withDefaults(
    defineProps<{
        modelValue?: string;
    }>(),
    {
        modelValue: '1K',
    },
);

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
    (e: 'changeQuality', quality: string): void;
}>();

const onSelect = (val: any) => {
    const stringVal = String(val ?? '');
    emit('update:modelValue', stringVal);
    const opt = RESOLUTIONS.find((r) => r.value === stringVal);
    if (opt) {
        emit('changeQuality', opt.quality);
    }
};
</script>

<template>
    <div class="space-y-1.5">
        <Label
            for="resolution-select"
            class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
        >
            Resolution
        </Label>

        <Select :model-value="modelValue" @update:model-value="onSelect">
            <SelectTrigger
                id="resolution-select"
                class="h-9.5 w-full rounded-lg border-input/80 bg-background/60 px-3 text-xs font-medium dark:bg-card/60"
            >
                <SelectValue :placeholder="modelValue" />
            </SelectTrigger>

            <SelectContent>
                <SelectItem
                    v-for="res in RESOLUTIONS"
                    :key="res.value"
                    :value="res.value"
                    class="cursor-pointer text-xs"
                >
                    <span>{{ res.label }}</span>
                </SelectItem>
            </SelectContent>
        </Select>
    </div>
</template>
