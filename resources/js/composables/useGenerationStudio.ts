import { router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import { useCreditBalance } from '@/composables/useCreditBalance';
import { postJson } from '@/lib/http';
import { imageStudio } from '@/routes';
import { store as storeGenerationRoute } from '@/routes/generations';
import type {
    GenerationFormState,
    GenerationItem,
    GenerationSubmissionResponse,
} from '@/types/generation';

const DEFAULT_STATE: GenerationFormState = {
    prompt: '',
    size: '1536x1024',
    quality: 'low',
    aspectRatio: '16:9',
    resolution: '1K',
    numGenerations: 1,
    referenceImages: [],
};

function generateIdempotencyKey(): string {
    if (
        typeof crypto !== 'undefined' &&
        typeof crypto.randomUUID === 'function'
    ) {
        return crypto.randomUUID();
    }

    return `idemp-${Date.now()}-${Math.random().toString(36).substring(2, 9)}`;
}

const form = ref<GenerationFormState>({ ...DEFAULT_STATE });
const isSubmitting = ref<boolean>(false);
const submitError = ref<string | null>(null);
const submittedGenerations = ref<GenerationItem[]>([]);
// Kept stable across network retries of identical inputs; reset on input change or successful enqueue
const pendingIdempotencyKey = ref<string | null>(null);

watch(
    () => [
        form.value.prompt,
        form.value.size,
        form.value.quality,
        form.value.numGenerations,
        ...(form.value.referenceImages || []),
    ],
    () => {
        pendingIdempotencyKey.value = null;
    },
);

export function useGenerationStudio() {
    const hasUnsavedChanges = computed(() => {
        return (
            form.value.prompt.trim().length > 0 ||
            form.value.referenceImages.length > 0
        );
    });

    const resetForm = () => {
        form.value = {
            prompt: '',
            size: DEFAULT_STATE.size,
            quality: DEFAULT_STATE.quality,
            aspectRatio: DEFAULT_STATE.aspectRatio,
            resolution: DEFAULT_STATE.resolution,
            numGenerations: DEFAULT_STATE.numGenerations,
            referenceImages: [],
        };
        submitError.value = null;
        pendingIdempotencyKey.value = null;
    };

    const hydrateGenerations = (items: GenerationItem[]) => {
        submittedGenerations.value = [...items];
    };

    const mergeUpdatedGenerations = (updated: GenerationItem[]) => {
        if (updated.length === 0) {
            return;
        }

        const updatedById = new Map(updated.map((item) => [item.id, item]));
        submittedGenerations.value = submittedGenerations.value.map(
            (existing) => updatedById.get(existing.id) ?? existing,
        );
    };

    const startNewGeneration = () => {
        if (hasUnsavedChanges.value) {
            const confirmed = window.confirm(
                'You have unsaved prompt changes. Discard and start a new generation?',
            );
            if (!confirmed) {
                return false;
            }
        }

        resetForm();

        if (
            typeof window !== 'undefined' &&
            window.location.pathname !== '/image-studio'
        ) {
            router.visit(imageStudio().url);
        }

        return true;
    };

    /**
     * Submit an asynchronous generation request with a retry-stable idempotency key.
     */
    const submitGeneration = async (): Promise<boolean> => {
        const promptText = form.value.prompt.trim();
        if (!promptText) {
            toast.error('Please enter a prompt before generating.');
            return false;
        }

        if (isSubmitting.value) {
            return false;
        }

        isSubmitting.value = true;
        submitError.value = null;

        if (!pendingIdempotencyKey.value) {
            pendingIdempotencyKey.value = generateIdempotencyKey();
        }

        const idempotencyKey = pendingIdempotencyKey.value;

        try {
            const cleanReferenceImages = (form.value.referenceImages || [])
                .map((url) => url.trim())
                .filter((url) => url.length > 0);

            const payload: Record<string, unknown> = {
                prompt: promptText,
                size: form.value.size,
                quality: form.value.quality,
                num_generations: form.value.numGenerations || 1,
                idempotency_key: idempotencyKey,
            };

            if (cleanReferenceImages.length > 0) {
                payload.reference_images = cleanReferenceImages;
            }

            const responseData = await postJson<GenerationSubmissionResponse>(
                storeGenerationRoute.url(),
                payload,
                {
                    headers: {
                        'Idempotency-Key': idempotencyKey,
                    },
                    defaultErrorMessage: 'Generation submission failed',
                },
            );

            if (responseData?.credit_balance !== undefined) {
                useCreditBalance().syncBalance(responseData.credit_balance);
            }

            const items = responseData?.generations || [];
            if (!responseData?.is_idempotent_replay) {
                const incomingIds = new Set(items.map((item) => item.id));
                submittedGenerations.value = [
                    ...items,
                    ...submittedGenerations.value.filter(
                        (existing) => !incomingIds.has(existing.id),
                    ),
                ];
            }

            pendingIdempotencyKey.value = null;

            const count = items.length;
            const msg =
                count > 1
                    ? `${count} generations queued · Processing in background`
                    : 'Generation queued · Processing in background';
            toast.success(msg);

            return true;
        } catch (err: unknown) {
            const msg =
                err instanceof Error
                    ? err.message
                    : 'Failed to submit generation';
            submitError.value = msg;
            toast.error(msg);
            return false;
        } finally {
            isSubmitting.value = false;
        }
    };

    return {
        form,
        isSubmitting,
        submitError,
        submittedGenerations,
        hasUnsavedChanges,
        resetForm,
        hydrateGenerations,
        mergeUpdatedGenerations,
        startNewGeneration,
        submitGeneration,
    };
}
