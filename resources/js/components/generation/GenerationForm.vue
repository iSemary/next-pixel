<script setup lang="ts">
import { Sparkles, Wand2 } from '@lucide/vue';
import { computed } from 'vue';
import AspectRatioSelector from '@/components/generation/AspectRatioSelector.vue';
import GenerationCountSelector from '@/components/generation/GenerationCountSelector.vue';
import PricingBreakdown from '@/components/generation/PricingBreakdown.vue';
import PromptInput from '@/components/generation/PromptInput.vue';
import ReferenceImageInputs from '@/components/generation/ReferenceImageInputs.vue';
import ResolutionSelector from '@/components/generation/ResolutionSelector.vue';
import { Button } from '@/components/ui/button';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
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

const { form } = useGenerationStudio();

const { estimate, isLoading, error, retry, totalFormattedUserCost } =
    usePricingEstimate(form);

const hasPrompt = computed(() => form.value.prompt.trim().length > 0);

const generateButtonText = computed(() => {
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
        <div class="space-y-1">
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
            <p class="text-xs leading-relaxed text-muted-foreground">
                See your estimated price before you generate. You only pay for
                images that succeed.
            </p>
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

            <!-- Generate Button (Disabled for Step 06 per instructions) -->
            <div class="space-y-2 pt-2">
                <TooltipProvider>
                    <Tooltip>
                        <TooltipTrigger as-child>
                            <span class="block w-full cursor-not-allowed">
                                <Button
                                    disabled
                                    size="lg"
                                    class="pointer-events-none h-12 w-full gap-2 rounded-xl bg-primary text-base font-semibold text-primary-foreground opacity-60 shadow-md transition-all"
                                >
                                    <Sparkles class="h-4.5 w-4.5" />
                                    <span>{{ generateButtonText }}</span>
                                </Button>
                            </span>
                        </TooltipTrigger>
                        <TooltipContent side="bottom" class="text-xs">
                            <p>
                                Generation execution will be available in Step
                                07.
                            </p>
                        </TooltipContent>
                    </Tooltip>
                </TooltipProvider>

                <p class="text-center text-[11px] text-muted-foreground/75">
                    Live pricing estimation active · Generation execution hooks
                    up in Step 07.
                </p>
            </div>
        </div>
    </div>
</template>
