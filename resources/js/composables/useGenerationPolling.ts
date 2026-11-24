import { computed, onUnmounted, ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import { useCreditBalance } from '@/composables/useCreditBalance';
import { getJson } from '@/lib/http';
import { index as indexGenerationsRoute } from '@/routes/generations';
import type {
    GenerationItem,
    GenerationPollResponse,
    GenerationSummaryStats,
} from '@/types/generation';

const POLL_INTERVAL_MS = 2500;

export function isGenerationInFlight(item: GenerationItem): boolean {
    return item.status === 'queued' || item.status === 'processing';
}

/**
 * Poll active (queued/processing) generation IDs automatically until all reach terminal status.
 */
export function useGenerationPolling(
    getItems: () => GenerationItem[],
    onUpdate: (
        updated: GenerationItem[],
        summary?: GenerationSummaryStats,
    ) => void,
) {
    const { syncBalance } = useCreditBalance();
    const isPolling = ref(false);
    let timer: ReturnType<typeof setInterval> | null = null;
    let inFlightRequest = false;

    const activeIds = computed(() =>
        getItems()
            .filter(isGenerationInFlight)
            .map((item) => item.id),
    );

    const stopPolling = () => {
        if (timer !== null) {
            clearInterval(timer);
            timer = null;
        }
        isPolling.value = false;
    };

    const pollOnce = async () => {
        const ids = activeIds.value;
        if (ids.length === 0 || inFlightRequest) {
            return;
        }

        inFlightRequest = true;

        try {
            const url = indexGenerationsRoute.url({
                query: { ids: ids.join(',') },
            });

            const previousMap = new Map(
                getItems().map((item) => [item.id, item]),
            );

            const data = await getJson<GenerationPollResponse>(url, {
                defaultErrorMessage: 'Failed to poll generation status',
            });

            if (data.credit_balance !== undefined) {
                syncBalance(data.credit_balance);
            }

            const updated = data.generations || [];

            for (const nextItem of updated) {
                const prev = previousMap.get(nextItem.id);
                if (!prev || prev.status === nextItem.status) {
                    continue;
                }

                if (nextItem.status === 'succeeded') {
                    toast.success('Image generation succeeded');
                } else if (nextItem.status === 'failed') {
                    toast.error(
                        nextItem.error_message ||
                            'Image generation failed (free — not charged).',
                    );
                }
            }

            onUpdate(updated, data.summary);
        } catch {
            // Transient poll error; next interval tick will retry silently
        } finally {
            inFlightRequest = false;
        }
    };

    const startPolling = () => {
        if (timer !== null || activeIds.value.length === 0) {
            return;
        }

        isPolling.value = true;
        timer = setInterval(() => {
            void pollOnce();
        }, POLL_INTERVAL_MS);
    };

    watch(
        activeIds,
        (ids) => {
            if (ids.length > 0) {
                startPolling();
            } else {
                stopPolling();
            }
        },
        { immediate: true },
    );

    onUnmounted(() => {
        stopPolling();
    });

    return {
        isPolling,
        activeCount: computed(() => activeIds.value.length),
        pollOnce,
        stopPolling,
    };
}
