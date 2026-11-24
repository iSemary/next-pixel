import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { dashboard } from '@/routes';
import type { GenerationFormState } from '@/types/generation';

const DEFAULT_STATE: GenerationFormState = {
    prompt: '',
    size: '1536x1024',
    quality: 'low',
    aspectRatio: '16:9',
    resolution: '1K',
    numGenerations: 1,
    referenceImages: [],
};

const form = ref<GenerationFormState>({ ...DEFAULT_STATE });

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
            window.location.pathname !== '/dashboard'
        ) {
            router.visit(dashboard().url);
        }

        return true;
    };

    return {
        form,
        hasUnsavedChanges,
        resetForm,
        startNewGeneration,
    };
}
