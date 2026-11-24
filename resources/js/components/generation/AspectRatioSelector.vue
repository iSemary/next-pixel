<script setup lang="ts">
import { computed } from 'vue';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

export interface AspectRatioOption {
    value: string;
    label: string;
    size: '1024x1024' | '1536x1024' | '1024x1536';
    width: number;
    height: number;
}

const ASPECT_RATIOS: AspectRatioOption[] = [
    { value: '9:16', label: '9:16', size: '1024x1536', width: 9, height: 16 },
    { value: '2:3', label: '2:3', size: '1024x1536', width: 10, height: 15 },
    { value: '3:4', label: '3:4', size: '1024x1536', width: 11, height: 14.5 },
    { value: '4:5', label: '4:5', size: '1024x1536', width: 12, height: 15 },
    { value: '1:1', label: '1:1', size: '1024x1024', width: 13, height: 13 },
    { value: '5:4', label: '5:4', size: '1536x1024', width: 15, height: 12 },
    { value: '4:3', label: '4:3', size: '1536x1024', width: 14.5, height: 11 },
    { value: '3:2', label: '3:2', size: '1536x1024', width: 15, height: 10 },
    { value: '16:9', label: '16:9', size: '1536x1024', width: 16, height: 9 },
    { value: '21:9', label: '21:9', size: '1536x1024', width: 17, height: 7.5 },
];

const props = withDefaults(
    defineProps<{
        modelValue?: string;
    }>(),
    {
        modelValue: '16:9',
    },
);

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
    (e: 'changeSize', size: string): void;
}>();

const selectedOption = computed(() => {
    return (
        ASPECT_RATIOS.find((r) => r.value === props.modelValue) ||
        ASPECT_RATIOS[8] // default 16:9
    );
});

const onSelect = (val: any) => {
    const stringVal = String(val ?? '');
    emit('update:modelValue', stringVal);
    const opt = ASPECT_RATIOS.find((r) => r.value === stringVal);
    if (opt) {
        emit('changeSize', opt.size);
    }
};
</script>

<template>
    <div class="space-y-1.5">
        <Label
            for="aspect-ratio-select"
            class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
        >
            Aspect Ratio
        </Label>

        <Select :model-value="modelValue" @update:model-value="onSelect">
            <SelectTrigger
                id="aspect-ratio-select"
                class="h-9.5 w-full rounded-lg border-input/80 bg-background/60 px-3 text-xs font-medium dark:bg-card/60"
            >
                <div class="flex items-center gap-2">
                    <span
                        class="flex h-4 w-4.5 shrink-0 items-center justify-center"
                    >
                        <span
                            class="rounded-[1.5px] border border-current opacity-80"
                            :style="{
                                width: `${selectedOption.width}px`,
                                height: `${selectedOption.height}px`,
                            }"
                        />
                    </span>
                    <SelectValue :placeholder="modelValue" />
                </div>
            </SelectTrigger>

            <SelectContent class="max-h-64">
                <SelectItem
                    v-for="ratio in ASPECT_RATIOS"
                    :key="ratio.value"
                    :value="ratio.value"
                    class="cursor-pointer text-xs"
                >
                    <div class="flex items-center gap-2">
                        <span
                            class="flex h-4 w-4.5 shrink-0 items-center justify-center"
                        >
                            <span
                                class="rounded-[1.5px] border border-current opacity-80"
                                :style="{
                                    width: `${ratio.width}px`,
                                    height: `${ratio.height}px`,
                                }"
                            />
                        </span>
                        <span>{{ ratio.label }}</span>
                    </div>
                </SelectItem>
            </SelectContent>
        </Select>
    </div>
</template>
