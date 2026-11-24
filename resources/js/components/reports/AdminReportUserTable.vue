<script setup lang="ts">
import { Users } from '@lucide/vue';
import { formatExactUsd } from '@/components/generation/generationStatusMeta';
import type { AdminReportUserRow } from '@/types/report';

defineProps<{
    users: AdminReportUserRow[];
}>();
</script>

<template>
    <section
        class="overflow-hidden rounded-xl border border-border/80 bg-card shadow-xs"
    >
        <div
            class="flex items-center justify-between border-b border-border/70 px-4 py-3.5"
        >
            <div class="flex items-center gap-2">
                <Users class="h-4 w-4 text-primary" />
                <div>
                    <h2 class="text-sm font-semibold">Breakdown by User</h2>
                    <p class="text-xs text-muted-foreground">
                        Credit spend, provider cost, profit, and remaining
                        balance per account.
                    </p>
                </div>
            </div>
            <span class="text-xs text-muted-foreground">
                {{ users.length }} accounts
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead
                    class="border-b border-border/70 bg-muted/40 text-[11px] font-semibold tracking-wider text-muted-foreground uppercase"
                >
                    <tr>
                        <th class="px-4 py-3">User</th>
                        <th class="px-4 py-3 text-right">Generations</th>
                        <th class="px-4 py-3 text-right">Tokens</th>
                        <th class="px-4 py-3 text-right">Credit Spend</th>
                        <th class="px-4 py-3 text-right">Provider Cost</th>
                        <th class="px-4 py-3 text-right">Profit</th>
                        <th class="px-4 py-3 text-right">Balance</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border/60">
                    <tr
                        v-for="user in users"
                        :key="user.user_id ?? user.email"
                        class="transition-colors hover:bg-muted/20"
                    >
                        <td class="px-4 py-3">
                            <span class="block font-medium text-foreground">
                                {{ user.name }}
                            </span>
                            <span class="text-[11px] text-muted-foreground">
                                {{ user.email }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right font-mono">
                            <span class="font-medium text-foreground">
                                {{ user.succeeded_generations }}
                            </span>
                            <span
                                v-if="user.failed_generations > 0"
                                class="ml-1 text-[10px] text-muted-foreground"
                            >
                                (+{{ user.failed_generations }} failed)
                            </span>
                        </td>
                        <td
                            class="px-4 py-3 text-right font-mono text-muted-foreground"
                        >
                            {{ user.total_tokens.toLocaleString() }}
                        </td>
                        <td class="px-4 py-3 text-right font-mono font-medium">
                            {{ formatExactUsd(user.credit_spend) }}
                        </td>
                        <td
                            class="px-4 py-3 text-right font-mono text-muted-foreground"
                        >
                            {{ formatExactUsd(user.provider_cost) }}
                        </td>
                        <td
                            class="px-4 py-3 text-right font-mono font-semibold text-emerald-600 dark:text-emerald-400"
                        >
                            {{ formatExactUsd(user.profit) }}
                        </td>
                        <td
                            class="px-4 py-3 text-right font-mono text-muted-foreground"
                        >
                            {{ formatExactUsd(user.credit_balance) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</template>
