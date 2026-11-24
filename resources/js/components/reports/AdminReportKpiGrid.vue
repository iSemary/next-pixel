<script setup lang="ts">
import { Coins, DollarSign, Server, TrendingUp } from '@lucide/vue';
import { formatExactUsd } from '@/components/generation/generationStatusMeta';
import type { AdminReportSummary } from '@/types/report';

defineProps<{
    summary: AdminReportSummary;
}>();
</script>

<template>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <!-- Credit Spend -->
        <div class="rounded-xl border border-border/80 bg-card p-4 shadow-xs">
            <div
                class="flex items-center justify-between text-xs text-muted-foreground"
            >
                <span>Credit Spend (Charged)</span>
                <Coins class="h-4 w-4 text-primary" />
            </div>
            <p class="mt-2 font-mono text-2xl font-bold">
                {{ formatExactUsd(summary.credit_spend) }}
            </p>
            <p class="mt-1 text-xs text-muted-foreground">
                Across {{ summary.succeeded_generations }} succeeded generations
            </p>
        </div>

        <!-- Provider Costs -->
        <div class="rounded-xl border border-border/80 bg-card p-4 shadow-xs">
            <div
                class="flex items-center justify-between text-xs text-muted-foreground"
            >
                <span>Provider Costs (Pixelforge)</span>
                <Server class="h-4 w-4 text-blue-500" />
            </div>
            <p class="mt-2 font-mono text-2xl font-bold">
                {{ formatExactUsd(summary.provider_cost) }}
            </p>
            <p class="mt-1 text-xs text-muted-foreground">
                Local: {{ formatExactUsd(summary.local_provider_cost) }}
                <span v-if="summary.pixelforge_provider_cost !== null">
                    · /v1/usage:
                    {{ formatExactUsd(summary.pixelforge_provider_cost) }}
                </span>
            </p>
        </div>

        <!-- Net Profit -->
        <div class="rounded-xl border border-border/80 bg-card p-4 shadow-xs">
            <div
                class="flex items-center justify-between text-xs text-muted-foreground"
            >
                <span>Net Profit</span>
                <TrendingUp class="h-4 w-4 text-emerald-500" />
            </div>
            <p
                class="mt-2 font-mono text-2xl font-bold text-emerald-600 dark:text-emerald-400"
            >
                {{ formatExactUsd(summary.profit) }}
            </p>
            <p class="mt-1 text-xs text-muted-foreground">
                {{ summary.margin_percent }}% margin on spend (+25% markup)
            </p>
        </div>

        <!-- Volume -->
        <div class="rounded-xl border border-border/80 bg-card p-4 shadow-xs">
            <div
                class="flex items-center justify-between text-xs text-muted-foreground"
            >
                <span>Volume &amp; Failed (Free)</span>
                <DollarSign class="h-4 w-4 text-muted-foreground" />
            </div>
            <p class="mt-2 font-mono text-2xl font-bold">
                {{
                    (
                        summary.succeeded_generations +
                        summary.failed_generations
                    ).toLocaleString()
                }}
            </p>
            <p class="mt-1 text-xs text-muted-foreground">
                {{ summary.succeeded_generations }} billed ·
                {{ summary.failed_generations }} failed ($0.0000)
            </p>
        </div>
    </div>
</template>
