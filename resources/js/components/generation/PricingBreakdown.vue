<script setup lang="ts">
import { ref, computed } from 'vue';
import {
    ChevronDown,
    ChevronUp,
    Loader2,
    AlertCircle,
    RefreshCw,
    CheckCircle2,
    ShieldCheck,
} from '@lucide/vue';
import { Button } from '@/components/ui/button';
import type { PricingEstimationResult } from '@/types/generation';

const props = withDefaults(
    defineProps<{
        estimate: PricingEstimationResult | null;
        isLoading: boolean;
        error: string | null;
        hasPrompt: boolean;
        numGenerations?: number;
    }>(),
    {
        numGenerations: 1,
    },
);

const emit = defineEmits<{
    (e: 'retry'): void;
}>();

const isExpanded = ref(false);

const toggleExpanded = () => {
    isExpanded.value = !isExpanded.value;
};

const formatCost = (costStr?: string, multiplier = 1): string => {
    if (!costStr) return '$0.0000';
    const num = parseFloat(costStr) * multiplier;
    if (isNaN(num)) return '$0.0000';
    return `$${num.toFixed(4)}`;
};

const totalFormattedCost = computed(() => {
    return formatCost(
        props.estimate?.estimated_user_cost,
        props.numGenerations,
    );
});

const unitFormattedCost = computed(() => {
    return formatCost(props.estimate?.estimated_user_cost, 1);
});

const categoriesList = computed(() => {
    if (!props.estimate?.categories) return [];
    return Object.values(props.estimate.categories).filter(Boolean);
});
</script>

<template>
    <div
        class="rounded-xl border border-border/80 bg-card/60 p-4 shadow-xs backdrop-blur-xs transition-all dark:bg-card/40"
    >
        <!-- Error State -->
        <div v-if="error" class="space-y-3">
            <div class="flex items-start gap-2.5 text-xs text-destructive">
                <AlertCircle class="mt-0.5 h-4 w-4 shrink-0" />
                <div class="flex-1 space-y-1">
                    <p class="font-medium">
                        Failed to calculate price estimate
                    </p>
                    <p class="text-[11px] text-muted-foreground">{{ error }}</p>
                </div>
            </div>
            <Button
                variant="outline"
                size="sm"
                type="button"
                class="h-7 w-full gap-1.5 text-xs"
                @click="emit('retry')"
            >
                <RefreshCw class="h-3 w-3" />
                Retry Price Estimation
            </Button>
        </div>

        <!-- No Prompt State -->
        <div
            v-else-if="!hasPrompt"
            class="py-2 text-center text-xs text-muted-foreground"
        >
            <p>
                Enter a prompt above to calculate live token and price
                estimation.
            </p>
        </div>

        <!-- Active / Estimated State -->
        <div v-else class="space-y-3">
            <!-- Header Summary -->
            <div class="flex items-center justify-between">
                <div class="space-y-0.5">
                    <span
                        class="text-[11px] font-semibold tracking-wider text-muted-foreground uppercase"
                    >
                        Estimated Cost
                        {{
                            numGenerations > 1
                                ? `(${numGenerations} images)`
                                : ''
                        }}
                    </span>
                    <div class="flex items-baseline gap-2">
                        <span
                            class="font-mono text-2xl font-bold tracking-tight text-foreground"
                        >
                            {{ estimate ? totalFormattedCost : '--' }}
                        </span>
                        <span
                            v-if="estimate"
                            class="font-mono text-xs text-muted-foreground"
                        >
                            USD
                        </span>
                    </div>
                    <p
                        v-if="estimate && numGenerations > 1"
                        class="font-mono text-[10px] text-muted-foreground"
                    >
                        {{ numGenerations }} × {{ unitFormattedCost }} per image
                    </p>
                </div>

                <div class="flex flex-col items-end gap-1">
                    <div
                        v-if="isLoading"
                        class="flex animate-pulse items-center gap-1.5 text-xs text-muted-foreground"
                    >
                        <Loader2
                            class="h-3.5 w-3.5 animate-spin text-primary"
                        />
                        <span>Updating...</span>
                    </div>
                    <div
                        v-else-if="estimate"
                        class="flex items-center gap-1 text-[11px] text-primary"
                    >
                        <CheckCircle2 class="h-3.5 w-3.5" />
                        <span>Live estimate</span>
                    </div>

                    <span
                        v-if="estimate"
                        class="font-mono text-[11px] text-muted-foreground"
                    >
                        {{ estimate.total_tokens.toLocaleString() }} total
                        tokens
                    </span>
                </div>
            </div>

            <!-- Expandable Breakdown Toggle -->
            <div v-if="estimate" class="border-t border-border/60 pt-2.5">
                <button
                    type="button"
                    class="flex w-full items-center justify-between py-1 text-xs font-medium text-muted-foreground transition-colors hover:text-foreground"
                    @click="toggleExpanded"
                >
                    <span class="flex items-center gap-1.5">
                        <span>Cost Breakdown</span>
                        <span
                            class="py-0.2 rounded bg-muted px-1.5 text-[10px] text-muted-foreground"
                        >
                            +{{ estimate.markup_percentage }} platform fee
                        </span>
                    </span>
                    <component
                        :is="isExpanded ? ChevronUp : ChevronDown"
                        class="h-3.5 w-3.5"
                    />
                </button>

                <!-- Detailed Breakdown List -->
                <div v-if="isExpanded" class="space-y-2 pt-2 text-xs">
                    <div
                        v-for="cat in categoriesList"
                        :key="cat?.category"
                        class="flex items-center justify-between py-0.5 text-muted-foreground"
                    >
                        <div class="flex flex-col">
                            <span
                                class="text-[11px] font-medium text-foreground"
                                >{{ cat?.label }}</span
                            >
                            <span
                                class="font-mono text-[10px] text-muted-foreground/70"
                            >
                                {{ cat?.estimated_tokens.toLocaleString() }}
                                tokens @ ${{ cat?.unit_rate_per_1m }}/1M
                            </span>
                        </div>
                        <span class="font-mono text-[11px] text-foreground">
                            {{ formatCost(cat?.estimated_cost_usd) }}
                        </span>
                    </div>

                    <div
                        class="space-y-1 border-t border-dashed border-border/60 pt-2"
                    >
                        <div
                            class="flex justify-between text-[11px] text-muted-foreground"
                        >
                            <span>Provider Cost</span>
                            <span class="font-mono">{{
                                formatCost(estimate.estimated_provider_cost)
                            }}</span>
                        </div>
                        <div
                            class="flex justify-between text-[11px] text-muted-foreground"
                        >
                            <span
                                >Platform Markup ({{
                                    estimate.markup_percentage
                                }})</span
                            >
                            <span class="font-mono"
                                >+{{ formatCost(estimate.markup_amount) }}</span
                            >
                        </div>
                        <div
                            class="flex justify-between border-t border-border/40 pt-1 text-xs font-semibold text-foreground"
                        >
                            <span>Estimated Total</span>
                            <span class="font-mono text-primary">{{
                                formatCost(estimate.estimated_user_cost)
                            }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Guarantee Notice -->
            <div
                class="flex items-center gap-1.5 rounded-md bg-secondary/50 px-2.5 py-1.5 text-[11px] text-muted-foreground"
            >
                <ShieldCheck class="h-3.5 w-3.5 shrink-0 text-primary" />
                <span
                    >You only pay for images that succeed. Failed generations
                    are never charged.</span
                >
            </div>
        </div>
    </div>
</template>
