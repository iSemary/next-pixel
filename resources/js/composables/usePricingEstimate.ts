import { computed, ref, watch, type Ref } from 'vue';
import type {
    GenerationFormState,
    PricingEstimationResult,
} from '@/types/generation';

function getXsrfToken(): string | null {
    if (typeof document === 'undefined') {
        return null;
    }
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : null;
}

export function usePricingEstimate(form: Ref<GenerationFormState>) {
    const estimate = ref<PricingEstimationResult | null>(null);
    const isLoading = ref<boolean>(false);
    const error = ref<string | null>(null);

    let activeAbortController: AbortController | null = null;
    let debounceTimer: ReturnType<typeof setTimeout> | null = null;

    /**
     * Estimated live input text tokens based on documented ~4 chars/token rule.
     */
    const estimatedTextTokens = computed(() => {
        const text = form.value.prompt.trim();
        if (!text) {
            return 0;
        }
        return Math.ceil(text.length / 4);
    });

    /**
     * Formatted user display cost.
     * Preserves fractional-cent precision (up to 4 decimal places for display, e.g. $0.0468).
     */
    const formattedUserCost = computed(() => {
        if (!estimate.value?.estimated_user_cost) {
            return null;
        }
        const numeric = parseFloat(estimate.value.estimated_user_cost);
        if (isNaN(numeric)) {
            return null;
        }
        // If cost is tiny (< $0.01), format with 4 decimal places, else 2-4 decimal places
        if (numeric < 0.01) {
            return `$${numeric.toFixed(4)}`;
        }
        return `$${numeric.toFixed(4).replace(/0+$/, '').replace(/\.$/, '')}`;
    });

    const numGenerations = computed(() => form.value.numGenerations || 1);

    const totalFormattedUserCost = computed(() => {
        if (!estimate.value?.estimated_user_cost) {
            return null;
        }
        const numeric =
            parseFloat(estimate.value.estimated_user_cost) *
            numGenerations.value;
        if (isNaN(numeric)) {
            return null;
        }
        if (numeric < 0.01) {
            return `$${numeric.toFixed(4)}`;
        }
        return `$${numeric.toFixed(4).replace(/0+$/, '').replace(/\.$/, '')}`;
    });

    /**
     * Validates whether reference images array contains only valid URLs.
     */
    const cleanReferenceImages = computed(() => {
        return (form.value.referenceImages || [])
            .map((url) => url.trim())
            .filter((url) => url.length > 0);
    });

    const hasInvalidUrl = computed(() => {
        for (const url of cleanReferenceImages.value) {
            try {
                const parsed = new URL(url);
                if (!['http:', 'https:'].includes(parsed.protocol)) {
                    return true;
                }
            } catch {
                return true;
            }
        }
        return false;
    });

    /**
     * Fetch pricing estimate from backend.
     */
    const fetchEstimate = async () => {
        // Abort previous in-flight request if any
        if (activeAbortController) {
            activeAbortController.abort();
            activeAbortController = null;
        }

        const promptText = form.value.prompt.trim();
        if (!promptText) {
            estimate.value = null;
            error.value = null;
            isLoading.value = false;
            return;
        }

        if (hasInvalidUrl.value) {
            error.value =
                'Please ensure all reference image URLs are valid before calculating estimate.';
            isLoading.value = false;
            return;
        }

        activeAbortController = new AbortController();
        const signal = activeAbortController.signal;

        isLoading.value = true;
        error.value = null;

        try {
            const xsrfToken = getXsrfToken();
            const headers: Record<string, string> = {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            };

            if (xsrfToken) {
                headers['X-XSRF-TOKEN'] = xsrfToken;
            }

            const payload: Record<string, unknown> = {
                prompt: promptText,
                size: form.value.size,
                quality: form.value.quality,
            };

            if (cleanReferenceImages.value.length > 0) {
                payload.reference_images = cleanReferenceImages.value;
            }

            const response = await fetch('/generations/estimate', {
                method: 'POST',
                headers,
                credentials: 'same-origin',
                body: JSON.stringify(payload),
                signal,
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => null);
                if (response.status === 422 && errorData?.errors) {
                    const firstError = Object.values(
                        errorData.errors,
                    )[0] as string[];
                    throw new Error(
                        firstError?.[0] ||
                            'Validation error while calculating estimate.',
                    );
                }
                throw new Error(
                    errorData?.message ||
                        `Estimate calculation failed (${response.status})`,
                );
            }

            const data: PricingEstimationResult = await response.json();
            estimate.value = data;
            error.value = null;
        } catch (err: unknown) {
            if (err instanceof DOMException && err.name === 'AbortError') {
                // Request aborted because a newer change arrived; ignore
                return;
            }
            const msg =
                err instanceof Error
                    ? err.message
                    : 'Failed to calculate estimate';
            error.value = msg;
            estimate.value = null;
        } finally {
            if (activeAbortController?.signal === signal) {
                isLoading.value = false;
                activeAbortController = null;
            }
        }
    };

    /**
     * Debounced estimate runner.
     */
    const scheduleEstimate = () => {
        if (debounceTimer) {
            clearTimeout(debounceTimer);
        }

        if (!form.value.prompt.trim()) {
            estimate.value = null;
            error.value = null;
            isLoading.value = false;
            return;
        }

        isLoading.value = true;
        debounceTimer = setTimeout(() => {
            void fetchEstimate();
        }, 350);
    };

    // Watch for changes in form state with deep equality
    watch(
        () => [
            form.value.prompt,
            form.value.size,
            form.value.quality,
            ...cleanReferenceImages.value,
        ],
        () => {
            scheduleEstimate();
        },
        { immediate: true },
    );

    const retry = () => {
        void fetchEstimate();
    };

    return {
        estimate,
        isLoading,
        error,
        retry,
        estimatedTextTokens,
        formattedUserCost,
        totalFormattedUserCost,
        numGenerations,
        hasInvalidUrl,
    };
}
