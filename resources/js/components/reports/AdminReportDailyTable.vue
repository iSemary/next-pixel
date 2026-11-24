<script setup lang="ts">
import { Calendar } from '@lucide/vue';
import { computed, ref } from 'vue';
import { formatExactUsd } from '@/components/generation/generationStatusMeta';
import {
    formatDateUtc,
    formatSignedDelta,
} from '@/components/reports/reportFormatters';
import { Button } from '@/components/ui/button';
import type {
    AdminReportDailyRow,
    AdminReportFilters,
    AdminReportSummary,
} from '@/types/report';

const props = defineProps<{
    daily: AdminReportDailyRow[];
    summary: AdminReportSummary;
    filters: AdminReportFilters;
}>();

const showEmptyDays = ref(false);

const visibleDailyRows = computed(() => {
    if (showEmptyDays.value) {
        return props.daily;
    }

    const active = props.daily.filter((row) => row.has_activity);

    return active.length > 0 ? active : props.daily.slice(0, 7);
});

const hiddenEmptyDaysCount = computed(
    () => props.daily.filter((row) => !row.has_activity).length,
);
</script>

<template>
    <section
        class="overflow-hidden rounded-xl border border-border/80 bg-card shadow-xs"
    >
        <div
            class="flex flex-col justify-between gap-2 border-b border-border/70 px-4 py-3.5 sm:flex-row sm:items-center"
        >
            <div class="flex items-center gap-2">
                <Calendar class="h-4 w-4 text-primary" />
                <div>
                    <h2 class="text-sm font-semibold">
                        Daily Breakdown (UTC Completion Date)
                    </h2>
                    <p class="text-xs text-muted-foreground">
                        Pixelforge bills each generation on the UTC day it
                        completes.
                    </p>
                </div>
            </div>

            <Button
                v-if="hiddenEmptyDaysCount > 0"
                size="sm"
                variant="outline"
                class="h-8 cursor-pointer text-xs"
                @click="showEmptyDays = !showEmptyDays"
            >
                {{
                    showEmptyDays
                        ? 'Hide zero-activity days'
                        : `Show all days (+${hiddenEmptyDaysCount} empty)`
                }}
            </Button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead
                    class="border-b border-border/70 bg-muted/40 text-[11px] font-semibold tracking-wider text-muted-foreground uppercase"
                >
                    <tr>
                        <th class="px-4 py-3">Date (UTC)</th>
                        <th class="px-4 py-3 text-right">Generations</th>
                        <th class="px-4 py-3 text-right">Credit Spend</th>
                        <th class="px-4 py-3 text-right">Local Cost</th>
                        <th class="px-4 py-3 text-right">
                            Pixelforge (/v1/usage)
                        </th>
                        <th class="px-4 py-3 text-center">Match</th>
                        <th class="px-4 py-3 text-right">Profit</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border/60">
                    <tr
                        v-for="row in visibleDailyRows"
                        :key="row.date"
                        class="transition-colors hover:bg-muted/20"
                    >
                        <td class="px-4 py-3 font-medium whitespace-nowrap">
                            <span class="block text-foreground">{{
                                formatDateUtc(row.date)
                            }}</span>
                            <span
                                class="font-mono text-[10px] text-muted-foreground"
                            >
                                {{ row.date }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right font-mono">
                            <span class="font-medium text-foreground">
                                {{ row.succeeded_generations }}
                            </span>
                            <span
                                v-if="row.failed_generations > 0"
                                class="ml-1 text-[10px] text-muted-foreground"
                            >
                                (+{{ row.failed_generations }} failed)
                            </span>
                            <span
                                v-if="
                                    row.pixelforge_generations !== null &&
                                    row.pixelforge_generations !==
                                        row.succeeded_generations
                                "
                                class="block text-[10px] text-amber-600 dark:text-amber-400"
                            >
                                PF: {{ row.pixelforge_generations }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right font-mono font-medium">
                            {{ formatExactUsd(row.credit_spend) }}
                        </td>
                        <td
                            class="px-4 py-3 text-right font-mono text-muted-foreground"
                        >
                            {{ formatExactUsd(row.local_provider_cost) }}
                        </td>
                        <td class="px-4 py-3 text-right font-mono">
                            <span v-if="row.pixelforge_cost !== null">
                                {{ formatExactUsd(row.pixelforge_cost) }}
                            </span>
                            <span v-else class="text-muted-foreground">—</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span
                                v-if="row.matches_pixelforge === true"
                                class="inline-flex items-center gap-1 rounded-full bg-emerald-500/10 px-2 py-0.5 text-[11px] font-medium text-emerald-700 dark:text-emerald-300"
                            >
                                Exact
                            </span>
                            <span
                                v-else-if="row.matches_pixelforge === false"
                                class="inline-flex items-center gap-1 rounded-full bg-amber-500/15 px-2 py-0.5 font-mono text-[11px] font-medium text-amber-700 dark:text-amber-300"
                            >
                                {{ formatSignedDelta(row.discrepancy) }}
                            </span>
                            <span v-else class="text-muted-foreground">—</span>
                        </td>
                        <td
                            class="px-4 py-3 text-right font-mono font-semibold text-emerald-600 dark:text-emerald-400"
                        >
                            {{ formatExactUsd(row.profit) }}
                        </td>
                    </tr>
                </tbody>
                <tfoot
                    class="border-t border-border bg-muted/30 font-mono text-xs font-semibold"
                >
                    <tr>
                        <td class="px-4 py-3 font-sans">
                            Range Total ({{ filters.start_date }} to
                            {{ filters.end_date }})
                        </td>
                        <td class="px-4 py-3 text-right">
                            {{ summary.succeeded_generations }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            {{ formatExactUsd(summary.credit_spend) }}
                        </td>
                        <td class="px-4 py-3 text-right text-muted-foreground">
                            {{ formatExactUsd(summary.local_provider_cost) }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            {{
                                summary.pixelforge_provider_cost !== null
                                    ? formatExactUsd(
                                          summary.pixelforge_provider_cost,
                                      )
                                    : '—'
                            }}
                        </td>
                        <td class="px-4 py-3 text-center font-sans">
                            <span
                                v-if="summary.matches_pixelforge === true"
                                class="text-emerald-600 dark:text-emerald-400"
                            >
                                Matched
                            </span>
                            <span
                                v-else-if="summary.matches_pixelforge === false"
                                class="font-mono text-amber-600 dark:text-amber-400"
                            >
                                {{ formatSignedDelta(summary.discrepancy) }}
                            </span>
                            <span v-else class="text-muted-foreground">—</span>
                        </td>
                        <td
                            class="px-4 py-3 text-right text-emerald-600 dark:text-emerald-400"
                        >
                            {{ formatExactUsd(summary.profit) }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </section>
</template>
