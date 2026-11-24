<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    CheckCircle2,
    DollarSign,
    Layers,
    Loader2,
    RotateCcw,
    Search,
    ShieldCheck,
    Wand2,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import GenerationCard from '@/components/generation/GenerationCard.vue';
import GenerationDetailDialog from '@/components/generation/GenerationDetailDialog.vue';
import { formatExactUsd } from '@/components/generation/generationStatusMeta';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useGenerationPolling } from '@/composables/useGenerationPolling';
import { dashboard, imageStudio } from '@/routes';
import type {
    GenerationItem,
    GenerationSummaryStats,
    LibraryFilters,
    PaginatedGenerations,
} from '@/types/generation';

const props = defineProps<{
    generations: PaginatedGenerations;
    filters: LibraryFilters;
    summary: GenerationSummaryStats;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
});

const items = ref<GenerationItem[]>([...props.generations.data]);
const summaryStats = ref<GenerationSummaryStats>({ ...props.summary });
const searchInput = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || 'all');
const qualityFilter = ref(props.filters.quality || 'all');
const sizeFilter = ref(props.filters.size || 'all');

const selectedGenerationId = ref<number | null>(null);
const isDetailOpen = ref(false);

watch(
    () => props.generations.data,
    (nextData) => {
        items.value = [...nextData];
    },
);

watch(
    () => props.summary,
    (nextSummary) => {
        summaryStats.value = { ...nextSummary };
    },
);

const { isPolling } = useGenerationPolling(
    () => items.value,
    (updated, nextSummary) => {
        if (updated.length > 0) {
            const byId = new Map(updated.map((u) => [u.id, u]));
            items.value = items.value.map(
                (existing) => byId.get(existing.id) ?? existing,
            );
        }
        if (nextSummary) {
            summaryStats.value = { ...nextSummary };
        }
    },
);

const selectedGeneration = computed<GenerationItem | null>(() => {
    if (selectedGenerationId.value === null) {
        return null;
    }

    return items.value.find((g) => g.id === selectedGenerationId.value) ?? null;
});

const hasActiveFilters = computed(
    () =>
        searchInput.value.trim() !== '' ||
        statusFilter.value !== 'all' ||
        qualityFilter.value !== 'all' ||
        sizeFilter.value !== 'all',
);

const STATUS_TABS = [
    { value: 'all', label: 'All' },
    { value: 'queued', label: 'Queued' },
    { value: 'processing', label: 'Processing' },
    { value: 'succeeded', label: 'Succeeded' },
    { value: 'failed', label: 'Failed' },
] as const;

let searchDebounce: ReturnType<typeof setTimeout> | null = null;

function applyFilters(page?: number): void {
    const query: Record<string, string | number> = {};

    if (searchInput.value.trim() !== '') {
        query.search = searchInput.value.trim();
    }
    if (statusFilter.value !== 'all') {
        query.status = statusFilter.value;
    }
    if (qualityFilter.value !== 'all') {
        query.quality = qualityFilter.value;
    }
    if (sizeFilter.value !== 'all') {
        query.size = sizeFilter.value;
    }
    if (page && page > 1) {
        query.page = page;
    }

    router.get(
        dashboard.url({ query }),
        {},
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
}

watch(searchInput, () => {
    if (searchDebounce) {
        clearTimeout(searchDebounce);
    }
    searchDebounce = setTimeout(() => {
        applyFilters(1);
    }, 300);
});

function setStatus(status: string): void {
    statusFilter.value = status;
    applyFilters(1);
}

function resetFilters(): void {
    searchInput.value = '';
    statusFilter.value = 'all';
    qualityFilter.value = 'all';
    sizeFilter.value = 'all';
    applyFilters(1);
}

function openDetail(gen: GenerationItem): void {
    selectedGenerationId.value = gen.id;
    isDetailOpen.value = true;
}

const totalSpendDiff = computed(() => {
    const est = parseFloat(summaryStats.value.total_estimated_cost);
    const act = parseFloat(summaryStats.value.total_actual_cost);
    if (isNaN(est) || isNaN(act)) {
        return null;
    }
    const saved = est - act;
    if (saved <= 0.000001) {
        return null;
    }
    return `$${saved.toFixed(4).replace(/0+$/, '').replace(/\.$/, '')}`;
});
</script>

<template>
    <Head title="Your Library" />

    <div class="flex flex-1 flex-col p-4 md:p-6">
        <div class="flex w-full flex-1 flex-col gap-6">
            <!-- Page Header -->
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="space-y-1">
                    <div class="flex items-center gap-2.5">
                        <h1
                            class="font-bricolage text-2xl font-bold tracking-tight text-foreground"
                        >
                            Your library
                        </h1>
                        <span
                            v-if="isPolling"
                            class="inline-flex items-center gap-1 rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-medium text-primary"
                        >
                            <Loader2 class="h-3.5 w-3.5 animate-spin" />
                            Live sync
                        </span>
                    </div>
                    <p class="text-sm text-muted-foreground">
                        Browse generations, filter history, and compare
                        estimated vs. actual settlement costs.
                    </p>
                </div>

                <div>
                    <Button
                        as-child
                        class="gap-2 bg-primary font-semibold text-primary-foreground shadow-xs hover:bg-primary/90"
                    >
                        <Link :href="imageStudio()">
                            <Wand2 class="h-4 w-4" />
                            <span>Create an image</span>
                        </Link>
                    </Button>
                </div>
            </div>

            <!-- Summary Metrics Bar -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div
                    class="rounded-2xl border border-border/80 bg-card p-4 shadow-2xs"
                >
                    <div
                        class="flex items-center justify-between text-xs text-muted-foreground"
                    >
                        <span>Total Generations</span>
                        <Layers class="h-4 w-4 text-primary" />
                    </div>
                    <p class="mt-2 font-bricolage text-2xl font-bold">
                        {{ summaryStats.total_count.toLocaleString() }}
                    </p>
                    <p class="mt-1 text-[11px] text-muted-foreground">
                        {{ summaryStats.in_flight_count }} in-flight right now
                    </p>
                </div>

                <div
                    class="rounded-2xl border border-border/80 bg-card p-4 shadow-2xs"
                >
                    <div
                        class="flex items-center justify-between text-xs text-muted-foreground"
                    >
                        <span>Succeeded</span>
                        <CheckCircle2 class="h-4 w-4 text-emerald-500" />
                    </div>
                    <p class="mt-2 font-bricolage text-2xl font-bold">
                        {{ summaryStats.succeeded_count.toLocaleString() }}
                    </p>
                    <p class="mt-1 text-[11px] text-muted-foreground">
                        Billed on actual token usage
                    </p>
                </div>

                <div
                    class="rounded-2xl border border-border/80 bg-card p-4 shadow-2xs"
                >
                    <div
                        class="flex items-center justify-between text-xs text-muted-foreground"
                    >
                        <span>Failed (Free)</span>
                        <ShieldCheck class="h-4 w-4 text-emerald-500" />
                    </div>
                    <p class="mt-2 font-bricolage text-2xl font-bold">
                        {{ summaryStats.failed_count.toLocaleString() }}
                    </p>
                    <p
                        class="mt-1 text-[11px] text-emerald-600 dark:text-emerald-400"
                    >
                        $0.00 charged on failures
                    </p>
                </div>

                <div
                    class="rounded-2xl border border-border/80 bg-card p-4 shadow-2xs"
                >
                    <div
                        class="flex items-center justify-between text-xs text-muted-foreground"
                    >
                        <span>Actual vs. Estimated Spend</span>
                        <DollarSign class="h-4 w-4 text-primary" />
                    </div>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span
                            class="font-bricolage font-mono text-2xl font-bold"
                        >
                            {{ formatExactUsd(summaryStats.total_actual_cost) }}
                        </span>
                        <span
                            class="font-mono text-xs text-muted-foreground line-through"
                        >
                            {{
                                formatExactUsd(
                                    summaryStats.total_estimated_cost,
                                )
                            }}
                        </span>
                    </div>
                    <p
                        v-if="totalSpendDiff"
                        class="mt-1 text-[11px] text-emerald-600 dark:text-emerald-400"
                    >
                        Saved {{ totalSpendDiff }} vs. upper estimates
                    </p>
                    <p v-else class="mt-1 text-[11px] text-muted-foreground">
                        Exact 9-decimal settled total
                    </p>
                </div>
            </div>

            <!-- Filter Bar -->
            <div
                class="flex flex-col gap-3 rounded-2xl border border-border/80 bg-card p-4 shadow-2xs lg:flex-row lg:items-center lg:justify-between"
            >
                <!-- Status Tabs -->
                <div class="flex flex-wrap items-center gap-1.5">
                    <button
                        v-for="tab in STATUS_TABS"
                        :key="tab.value"
                        type="button"
                        class="cursor-pointer rounded-lg px-3 py-1.5 text-xs font-medium transition-colors"
                        :class="
                            statusFilter === tab.value
                                ? 'bg-primary text-primary-foreground shadow-2xs'
                                : 'bg-muted/50 text-muted-foreground hover:bg-muted hover:text-foreground'
                        "
                        @click="setStatus(tab.value)"
                    >
                        {{ tab.label }}
                    </button>
                </div>

                <!-- Search & Dropdowns -->
                <div class="flex flex-wrap items-center gap-2.5">
                    <div class="relative min-w-[220px] flex-1 sm:flex-initial">
                        <Search
                            class="pointer-events-none absolute top-1/2 left-3 h-3.5 w-3.5 -translate-y-1/2 text-muted-foreground"
                        />
                        <Input
                            v-model="searchInput"
                            type="search"
                            placeholder="Search prompts..."
                            class="h-9 pl-8 text-xs"
                        />
                    </div>

                    <select
                        v-model="qualityFilter"
                        aria-label="Filter by quality"
                        class="h-9 rounded-md border border-input bg-background px-2.5 text-xs text-foreground focus:ring-2 focus:ring-primary/40 focus:outline-none"
                        @change="applyFilters(1)"
                    >
                        <option value="all">All qualities</option>
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                    </select>

                    <select
                        v-model="sizeFilter"
                        aria-label="Filter by size"
                        class="h-9 rounded-md border border-input bg-background px-2.5 text-xs text-foreground focus:ring-2 focus:ring-primary/40 focus:outline-none"
                        @change="applyFilters(1)"
                    >
                        <option value="all">All sizes</option>
                        <option value="1024x1024">1024×1024 (1:1)</option>
                        <option value="1536x1024">1536×1024 (3:2)</option>
                        <option value="1024x1536">1024×1536 (2:3)</option>
                    </select>

                    <Button
                        v-if="hasActiveFilters"
                        type="button"
                        variant="ghost"
                        size="sm"
                        class="h-9 gap-1.5 text-xs text-muted-foreground"
                        @click="resetFilters"
                    >
                        <RotateCcw class="h-3.5 w-3.5" />
                        Reset
                    </Button>
                </div>
            </div>

            <!-- Results Grid -->
            <div v-if="items.length > 0" class="space-y-6">
                <div
                    class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
                >
                    <GenerationCard
                        v-for="gen in items"
                        :key="gen.id"
                        :generation="gen"
                        @select="openDetail"
                    />
                </div>

                <!-- Pagination -->
                <div
                    v-if="generations.last_page > 1"
                    class="flex flex-col items-center justify-between gap-3 border-t border-border/60 pt-4 sm:flex-row"
                >
                    <p class="text-xs text-muted-foreground">
                        Showing {{ generations.from }}–{{ generations.to }} of
                        {{ generations.total }} generations
                    </p>

                    <div class="flex items-center gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            :disabled="!generations.prev_page_url"
                            @click="applyFilters(generations.current_page - 1)"
                        >
                            Previous
                        </Button>
                        <span
                            class="px-2 font-mono text-xs text-muted-foreground"
                        >
                            Page {{ generations.current_page }} of
                            {{ generations.last_page }}
                        </span>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            :disabled="!generations.next_page_url"
                            @click="applyFilters(generations.current_page + 1)"
                        >
                            Next
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div
                v-else
                class="flex flex-1 flex-col items-center justify-center rounded-2xl border border-dashed border-border/80 bg-card/40 p-8 text-center sm:p-16 dark:bg-card/70"
            >
                <div
                    class="relative mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-muted/60 text-muted-foreground ring-1 ring-border/80 dark:bg-card/80"
                >
                    <Layers
                        class="h-8 w-8 stroke-[1.5] text-muted-foreground"
                    />
                    <div
                        class="absolute -top-1 -right-1 flex h-6 w-6 items-center justify-center rounded-full bg-primary/20 text-primary"
                    >
                        <Wand2 class="h-3.5 w-3.5" />
                    </div>
                </div>

                <h3
                    class="font-bricolage text-base font-semibold text-foreground"
                >
                    {{
                        hasActiveFilters
                            ? 'No generations match your filters'
                            : 'No generations in your library'
                    }}
                </h3>

                <p
                    class="mt-1.5 max-w-sm text-xs leading-relaxed text-muted-foreground"
                >
                    {{
                        hasActiveFilters
                            ? 'Try adjusting or clearing your search query, status, or quality filters.'
                            : 'Generations you create in Image Studio will appear here for you to view, download, and inspect costs.'
                    }}
                </p>

                <div class="mt-6">
                    <Button
                        v-if="hasActiveFilters"
                        type="button"
                        variant="outline"
                        class="gap-2"
                        @click="resetFilters"
                    >
                        <RotateCcw class="h-3.5 w-3.5" />
                        <span>Clear filters</span>
                    </Button>
                    <Button
                        v-else
                        as-child
                        variant="outline"
                        class="gap-2 border-border/80 bg-background/80 hover:bg-accent"
                    >
                        <Link :href="imageStudio()">
                            <Wand2 class="h-3.5 w-3.5 text-primary" />
                            <span>Create an image</span>
                        </Link>
                    </Button>
                </div>
            </div>
        </div>

        <GenerationDetailDialog
            v-model:open="isDetailOpen"
            :generation="selectedGeneration"
        />
    </div>
</template>
