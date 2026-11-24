<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import AdminReportDailyTable from '@/components/reports/AdminReportDailyTable.vue';
import AdminReportHeader from '@/components/reports/AdminReportHeader.vue';
import AdminReportKpiGrid from '@/components/reports/AdminReportKpiGrid.vue';
import AdminReportUserTable from '@/components/reports/AdminReportUserTable.vue';
import { index as reportsIndex } from '@/routes/admin/reports';
import type { AdminReportPageProps } from '@/types/report';

const props = defineProps<AdminReportPageProps>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Reports',
                href: reportsIndex(),
            },
            {
                title: 'Admin Report',
                href: reportsIndex(),
            },
        ],
    },
});

const startDate = ref(props.filters.start_date);
const endDate = ref(props.filters.end_date);
const isLoading = ref(false);

watch(
    () => props.filters,
    (next) => {
        startDate.value = next.start_date;
        endDate.value = next.end_date;
    },
);

function navigateWithDates(start: string, end: string): void {
    isLoading.value = true;
    router.get(
        reportsIndex.url({
            query: {
                start_date: start,
                end_date: end,
            },
        }),
        {},
        {
            preserveState: true,
            preserveScroll: true,
            onFinish: () => {
                isLoading.value = false;
            },
        },
    );
}

function handleApply(): void {
    if (!startDate.value || !endDate.value) {
        return;
    }

    navigateWithDates(startDate.value, endDate.value);
}

function handlePreset(days: number): void {
    const todayUtc = new Date();
    const end = todayUtc.toISOString().slice(0, 10);
    const startObj = new Date(todayUtc.getTime());
    startObj.setUTCDate(startObj.getUTCDate() - (days - 1));
    const start = startObj.toISOString().slice(0, 10);

    startDate.value = start;
    endDate.value = end;
    navigateWithDates(start, end);
}

function handleSync(): void {
    navigateWithDates(startDate.value, endDate.value);
}
</script>

<template>
    <Head title="Admin Report" />

    <div class="flex flex-1 flex-col p-4 md:p-6">
        <div class="flex w-full flex-1 flex-col gap-6">
            <AdminReportHeader
                v-model:start-date="startDate"
                v-model:end-date="endDate"
                :is-loading="isLoading"
                @apply="handleApply"
                @preset="handlePreset"
                @sync="handleSync"
            />

            <AdminReportKpiGrid :summary="summary" />

            <AdminReportDailyTable
                :daily="daily"
                :summary="summary"
                :filters="filters"
            />

            <AdminReportUserTable :users="by_user" />
        </div>
    </div>
</template>
