<script setup lang="ts">
import { AlertTriangle, Coins, Loader2, Sparkles, Wand2 } from '@lucide/vue';
import { computed } from 'vue';
import AspectRatioSelector from '@/components/generation/AspectRatioSelector.vue';
import GenerationCountSelector from '@/components/generation/GenerationCountSelector.vue';
import PricingBreakdown from '@/components/generation/PricingBreakdown.vue';
import PromptInput from '@/components/generation/PromptInput.vue';
import ReferenceImageInputs from '@/components/generation/ReferenceImageInputs.vue';
import ResolutionSelector from '@/components/generation/ResolutionSelector.vue';
import { Button } from '@/components/ui/button';
import { useCreditBalance } from '@/composables/useCreditBalance';
import { useGenerationStudio } from '@/composables/useGenerationStudio';
import { usePricingEstimate } from '@/composables/usePricingEstimate';
import type { ImageQualityOption, ImageSizeOption } from '@/types/generation';

withDefaults(
    defineProps<{
        supportedSizes?: ImageSizeOption[];
        supportedQualities?: ImageQualityOption[];
        testingKeywords?: string[];
    }>(),
    {
        supportedSizes: () => [],
        supportedQualities: () => [],
        testingKeywords: () => [],
    },
);

const { form, isSubmitting, submitGeneration } = useGenerationStudio();

const {
    estimate,
    isLoading,
    error,
    retry,
    totalFormattedUserCost,
    hasInvalidUrl,
} = usePricingEstimate(form);

const { numericBalance, formattedBalance, openBuyCredits } = useCreditBalance();

const hasPrompt = computed(() => form.value.prompt.trim().length > 0);

const totalEstimatedCost = computed(() => {
    if (!estimate.value?.estimated_user_cost) {
        return 0;
    }
    const perUnit = parseFloat(estimate.value.estimated_user_cost);
    if (isNaN(perUnit)) {
        return 0;
    }
    return perUnit * (form.value.numGenerations || 1);
});

const hasInsufficientCredits = computed(() => {
    if (!estimate.value || totalEstimatedCost.value <= 0) {
        return false;
    }
    return numericBalance.value + 1e-9 < totalEstimatedCost.value;
});

const generateButtonText = computed(() => {
    if (hasInsufficientCredits.value) {
        return 'Insufficient credits';
    }
    const count = form.value.numGenerations || 1;
    const cost = totalFormattedUserCost.value;
    if (count > 1) {
        return cost ? `Generate ${count} • ~${cost}` : `Generate ${count}`;
    }
    return cost ? `Generate • ~${cost}` : 'Generate';
});
</script>

<template>
    <div
        class="flex flex-col gap-6 rounded-2xl border border-border/80 bg-card p-5 shadow-xs dark:bg-card/90"
    >
        <!-- Section Header -->
        <div class="flex items-center gap-2">
            <div
                class="flex h-7 w-7 items-center justify-center rounded-lg bg-primary/15 text-primary"
            >
                <Wand2 class="h-4 w-4" />
            </div>
            <h2
                class="font-bricolage text-base font-semibold tracking-tight text-foreground"
            >
                Create an image
            </h2>
        </div>

        <!-- Generation Inputs -->
        <div class="space-y-5">
            <!-- Prompt -->
            <PromptInput
                v-model="form.prompt"
                :testing-keywords="testingKeywords"
            />

            <!-- Aspect Ratio and Resolution Row -->
            <div class="grid grid-cols-2 gap-3">
                <AspectRatioSelector
                    v-model="form.aspectRatio"
                    @change-size="(size) => (form.size = size)"
                />
                <ResolutionSelector
                    v-model="form.resolution"
                    @change-quality="(quality) => (form.quality = quality)"
                />
            </div>

            <!-- Number of Generations -->
            <GenerationCountSelector v-model="form.numGenerations" />

            <!-- Reference Images -->
            <ReferenceImageInputs v-model="form.referenceImages" />

            <!-- Pricing Breakdown -->
            <PricingBreakdown
                :estimate="estimate"
                :is-loading="isLoading"
                :error="error"
                :has-prompt="hasPrompt"
                :num-generations="form.numGenerations || 1"
                @retry="retry"
            />

            <!-- Insufficient Credits Alert -->
            <div
                v-if="hasInsufficientCredits"
                class="flex items-start justify-between gap-3 rounded-xl border border-amber-500/40 bg-amber-500/10 p-3 text-xs text-amber-800 dark:text-amber-300"
                data-test="insufficient-credits-alert"
            >
                <div class="flex items-start gap-2">
                    <AlertTriangle class="mt-0.5 h-4 w-4 shrink-0" />
                    <div class="space-y-0.5">
                        <p class="font-semibold">Not enough credits</p>
                        <p class="text-[11px] opacity-90">
                            Need ~{{ totalFormattedUserCost }}, available
                            {{ formattedBalance }}.
                        </p>
                    </div>
                </div>
                <Button
                    type="button"
                    size="sm"
                    variant="outline"
                    class="h-7 shrink-0 gap-1 border-amber-500/40 bg-background/80 text-xs font-semibold hover:bg-primary hover:text-primary-foreground"
                    @click="openBuyCredits"
                >
                    <Coins class="h-3.5 w-3.5" />
                    Buy credits
                </Button>
            </div>

            <!-- Generate Button -->
            <div class="space-y-2 pt-2">
                <Button
                    type="button"
                    :disabled="
                        !hasPrompt ||
                        hasInvalidUrl ||
                        hasInsufficientCredits ||
                        isSubmitting ||
                        isLoading
                    "
                    size="lg"
                    class="h-12 w-full gap-2 rounded-xl bg-primary text-base font-semibold text-primary-foreground shadow-md transition-all hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-50"
                    @click="submitGeneration"
                >
                    <Loader2
                        v-if="isSubmitting"
                        class="h-4.5 w-4.5 animate-spin"
                    />
                    <Sparkles v-else class="h-4.5 w-4.5" />
                    <span>{{
                        isSubmitting
                            ? 'Queueing generation...'
                            : generateButtonText
                    }}</span>
                </Button>
            </div>
        </div>
    </div>
</template>
