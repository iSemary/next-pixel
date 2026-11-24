<script setup lang="ts">
import { RefreshCw } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

const props = defineProps<{
    startDate: string;
    endDate: string;
    isLoading: boolean;
}>();

const emit = defineEmits<{
    (e: 'update:startDate', val: string): void;
    (e: 'update:endDate', val: string): void;
    (e: 'apply'): void;
    (e: 'preset', days: number): void;
    (e: 'sync'): void;
}>();
</script>

<template>
    <div
        class="flex flex-col justify-between gap-4 border-b border-border/70 pb-5 lg:flex-row lg:items-end"
    >
        <div class="space-y-1">
            <h1 class="font-bricolage text-2xl font-bold tracking-tight">
                Admin Financial Report
            </h1>
            <p class="text-sm text-muted-foreground">
                Operator view of credit spend, live Pixelforge provider costs
                (UTC completion day), and net profit across every day and user.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <div
                class="flex items-center gap-1 rounded-lg border border-border/80 bg-card p-1"
            >
                <Button
                    size="sm"
                    variant="ghost"
                    class="h-7 cursor-pointer px-2.5 text-xs"
                    :disabled="isLoading"
                    @click="emit('preset', 7)"
                >
                    7D
                </Button>
                <Button
                    size="sm"
                    variant="ghost"
                    class="h-7 cursor-pointer px-2.5 text-xs"
                    :disabled="isLoading"
                    @click="emit('preset', 30)"
                >
                    30D
                </Button>
                <Button
                    size="sm"
                    variant="ghost"
                    class="h-7 cursor-pointer px-2.5 text-xs"
                    :disabled="isLoading"
                    @click="emit('preset', 90)"
                >
                    90D
                </Button>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <Input
                    :model-value="startDate"
                    type="date"
                    class="h-9 w-[142px] bg-card text-xs"
                    aria-label="Start date (UTC)"
                    @update:model-value="
                        emit('update:startDate', String($event))
                    "
                />
                <span class="text-xs text-muted-foreground">to</span>
                <Input
                    :model-value="endDate"
                    type="date"
                    class="h-9 w-[142px] bg-card text-xs"
                    aria-label="End date (UTC)"
                    @update:model-value="emit('update:endDate', String($event))"
                />
                <Button
                    size="sm"
                    variant="outline"
                    class="h-9 cursor-pointer"
                    :disabled="isLoading"
                    @click="emit('apply')"
                >
                    Apply
                </Button>
                <Button
                    size="sm"
                    class="h-9 cursor-pointer bg-primary font-semibold text-primary-foreground hover:bg-primary/90"
                    :disabled="isLoading"
                    @click="emit('sync')"
                >
                    <RefreshCw
                        class="mr-1.5 h-3.5 w-3.5"
                        :class="{ 'animate-spin': isLoading }"
                    />
                    Sync /v1/usage
                </Button>
            </div>
        </div>
    </div>
</template>
