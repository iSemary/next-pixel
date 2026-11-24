<script setup lang="ts">
import { AlertCircle, Download, ExternalLink, ShieldCheck } from '@lucide/vue';
import { computed } from 'vue';
import {
    formatExactUsd,
    formatGenerationErrorTitle,
    GENERATION_STATUS_META,
} from '@/components/generation/generationStatusMeta';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type { GenerationItem } from '@/types/generation';

const props = defineProps<{
    generation: GenerationItem | null;
    open: boolean;
}>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
}>();

const statusMeta = computed(() =>
    props.generation ? GENERATION_STATUS_META[props.generation.status] : null,
);

const costVariance = computed(() => {
    const gen = props.generation;
    if (
        !gen ||
        gen.estimated_user_cost === null ||
        gen.actual_user_cost === null
    ) {
        return null;
    }

    const est = parseFloat(gen.estimated_user_cost);
    const act = parseFloat(gen.actual_user_cost);
    if (isNaN(est) || isNaN(act)) {
        return null;
    }

    const diff = act - est;
    const prefix = diff > 0 ? '+' : diff < 0 ? '-' : '';

    return {
        formatted: `${prefix}$${Math.abs(diff).toFixed(6).replace(/0+$/, '').replace(/\.$/, '') || '0'}`,
        saved: diff < 0,
    };
});

const tokenRows = computed(() => {
    const gen = props.generation;
    if (!gen) {
        return [];
    }

    const reasoningEst =
        gen.estimated_reasoning_tokens_min !== null &&
        gen.estimated_reasoning_tokens_max !== null
            ? `${gen.estimated_reasoning_tokens_min.toLocaleString()}–${gen.estimated_reasoning_tokens_max.toLocaleString()}`
            : '—';

    return [
        {
            label: 'Input text tokens',
            estimated:
                gen.estimated_input_text_tokens !== null
                    ? `≤ ${gen.estimated_input_text_tokens.toLocaleString()}`
                    : '—',
            actual:
                gen.actual_input_text_tokens !== null
                    ? gen.actual_input_text_tokens.toLocaleString()
                    : '—',
        },
        {
            label: 'Input image tokens',
            estimated:
                gen.estimated_input_image_tokens !== null
                    ? gen.estimated_input_image_tokens.toLocaleString()
                    : '—',
            actual:
                gen.actual_input_image_tokens !== null
                    ? gen.actual_input_image_tokens.toLocaleString()
                    : '—',
        },
        {
            label: 'Output tokens',
            estimated:
                gen.estimated_output_tokens !== null
                    ? gen.estimated_output_tokens.toLocaleString()
                    : '—',
            actual:
                gen.actual_output_tokens !== null
                    ? gen.actual_output_tokens.toLocaleString()
                    : '—',
        },
        {
            label: 'Reasoning tokens',
            estimated: reasoningEst,
            actual:
                gen.actual_reasoning_tokens !== null
                    ? gen.actual_reasoning_tokens.toLocaleString()
                    : '—',
        },
    ];
});

function formatDateTime(value: string | null): string {
    if (!value) {
        return '—';
    }
    return new Date(value).toLocaleString();
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent
            v-if="generation && statusMeta"
            class="max-h-[90vh] overflow-y-auto sm:max-w-2xl"
        >
            <DialogHeader>
                <div class="flex flex-wrap items-center gap-2.5 pr-6">
                    <span
                        class="inline-flex items-center gap-1.5 rounded-md px-2.5 py-0.5 text-xs font-medium"
                        :class="statusMeta.badgeClass"
                    >
                        <component
                            :is="statusMeta.icon"
                            class="h-3.5 w-3.5"
                            :class="{ 'animate-spin': statusMeta.spin }"
                        />
                        {{ statusMeta.label }}
                    </span>
                    <span class="font-mono text-xs text-muted-foreground">
                        {{ generation.size }} ·
                        <span class="capitalize">{{ generation.quality }}</span>
                    </span>
                </div>
                <DialogTitle class="pt-1 font-bricolage text-lg">
                    Generation Details
                </DialogTitle>
                <DialogDescription
                    class="text-sm leading-relaxed text-foreground"
                >
                    {{ generation.prompt }}
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-5 pt-2">
                <!-- Image Preview or Failure Alert -->
                <div
                    v-if="generation.image_url"
                    class="space-y-3 overflow-hidden rounded-xl border border-border/80 bg-muted/30 p-3"
                >
                    <img
                        :src="generation.image_url"
                        :alt="generation.prompt"
                        class="max-h-[380px] w-full rounded-lg object-contain"
                    />
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-xs text-muted-foreground">
                            URL expires:
                            {{
                                formatDateTime(generation.image_url_expires_at)
                            }}
                        </span>
                        <div class="flex items-center gap-2">
                            <Button as-child size="sm" variant="outline">
                                <a
                                    :href="generation.image_url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    <ExternalLink class="mr-1.5 h-3.5 w-3.5" />
                                    Open
                                </a>
                            </Button>
                            <Button as-child size="sm">
                                <a
                                    :href="generation.image_url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    download
                                >
                                    <Download class="mr-1.5 h-3.5 w-3.5" />
                                    Download PNG
                                </a>
                            </Button>
                        </div>
                    </div>
                </div>

                <div
                    v-else-if="generation.status === 'failed'"
                    class="flex items-start gap-3 rounded-xl border border-red-500/30 bg-red-500/10 p-4 text-sm text-red-700 dark:text-red-300"
                >
                    <AlertCircle class="mt-0.5 h-4 w-4 shrink-0" />
                    <div class="space-y-1">
                        <p class="font-semibold">
                            {{
                                formatGenerationErrorTitle(
                                    generation.error_code,
                                )
                            }}
                        </p>
                        <p
                            class="text-xs leading-relaxed text-red-700/90 dark:text-red-200/90"
                        >
                            {{
                                generation.error_message ||
                                'Generation failed on the upstream provider.'
                            }}
                        </p>
                    </div>
                </div>

                <!-- Failed generations are free callout -->
                <div
                    v-if="generation.status === 'failed'"
                    class="flex items-center gap-2.5 rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-xs text-emerald-700 dark:text-emerald-300"
                >
                    <ShieldCheck class="h-4 w-4 shrink-0" />
                    <span>
                        Failed generations are never charged — settled at
                        <strong class="font-mono">$0.0000</strong>.
                    </span>
                </div>

                <!-- Estimated vs Actual Breakdown -->
                <div
                    class="overflow-hidden rounded-xl border border-border/80 bg-card"
                >
                    <div
                        class="flex items-center justify-between border-b border-border/70 bg-muted/30 px-4 py-2.5"
                    >
                        <h4
                            class="text-xs font-semibold tracking-wider uppercase"
                        >
                            Estimated vs. Actual Breakdown
                        </h4>
                        <span
                            v-if="generation.is_settled"
                            class="rounded-full bg-emerald-500/10 px-2 py-0.5 text-[11px] font-medium text-emerald-600 dark:text-emerald-400"
                        >
                            Settled
                        </span>
                        <span
                            v-else
                            class="rounded-full bg-muted px-2 py-0.5 text-[11px] font-medium text-muted-foreground"
                        >
                            Pending settlement
                        </span>
                    </div>

                    <div class="divide-y divide-border/60 text-xs">
                        <div
                            class="grid grid-cols-3 bg-muted/15 px-4 py-2 font-medium text-muted-foreground"
                        >
                            <span>Metric</span>
                            <span class="text-right">Estimated</span>
                            <span class="text-right">Actual</span>
                        </div>

                        <div
                            v-for="row in tokenRows"
                            :key="row.label"
                            class="grid grid-cols-3 px-4 py-2"
                        >
                            <span class="text-muted-foreground">{{
                                row.label
                            }}</span>
                            <span class="text-right font-mono">{{
                                row.estimated
                            }}</span>
                            <span class="text-right font-mono font-medium">{{
                                row.actual
                            }}</span>
                        </div>

                        <div
                            class="grid grid-cols-3 bg-muted/15 px-4 py-2.5 font-semibold"
                        >
                            <span>Total tokens</span>
                            <span class="text-right font-mono">
                                {{
                                    generation.estimated_total_tokens_max !==
                                    null
                                        ? generation.estimated_total_tokens_max.toLocaleString()
                                        : '—'
                                }}
                            </span>
                            <span class="text-right font-mono">
                                {{
                                    generation.actual_total_tokens !== null
                                        ? generation.actual_total_tokens.toLocaleString()
                                        : '—'
                                }}
                            </span>
                        </div>

                        <div class="grid grid-cols-3 px-4 py-2">
                            <span class="text-muted-foreground"
                                >Provider cost (USD)</span
                            >
                            <span class="text-right font-mono">
                                {{
                                    formatExactUsd(
                                        generation.estimated_provider_cost,
                                    )
                                }}
                            </span>
                            <span class="text-right font-mono">
                                {{
                                    formatExactUsd(
                                        generation.actual_provider_cost,
                                    )
                                }}
                            </span>
                        </div>

                        <div
                            class="grid grid-cols-3 bg-primary/5 px-4 py-3 font-semibold"
                        >
                            <span>User cost (incl. markup)</span>
                            <span class="text-right font-mono">
                                {{
                                    formatExactUsd(
                                        generation.estimated_user_cost,
                                    )
                                }}
                            </span>
                            <div class="text-right font-mono">
                                <span>
                                    {{
                                        formatExactUsd(
                                            generation.actual_user_cost,
                                        )
                                    }}
                                </span>
                                <span
                                    v-if="costVariance"
                                    class="ml-1.5 text-[10px]"
                                    :class="
                                        costVariance.saved
                                            ? 'text-emerald-600 dark:text-emerald-400'
                                            : 'text-amber-600 dark:text-amber-400'
                                    "
                                >
                                    ({{ costVariance.formatted }})
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Audit Metadata -->
                <div
                    class="grid grid-cols-1 gap-2 rounded-xl border border-border/70 bg-muted/20 p-3.5 text-[11px] text-muted-foreground sm:grid-cols-2"
                >
                    <div>
                        <span class="font-medium text-foreground"
                            >Provider ID:</span
                        >
                        <span class="ml-1 font-mono">{{
                            generation.provider_generation_id || 'Pending...'
                        }}</span>
                    </div>
                    <div>
                        <span class="font-medium text-foreground"
                            >Submitted:</span
                        >
                        <span class="ml-1">{{
                            formatDateTime(generation.submitted_at)
                        }}</span>
                    </div>
                    <div>
                        <span class="font-medium text-foreground"
                            >Completed:</span
                        >
                        <span class="ml-1">{{
                            formatDateTime(generation.completed_at)
                        }}</span>
                    </div>
                    <div>
                        <span class="font-medium text-foreground"
                            >Settled:</span
                        >
                        <span class="ml-1">{{
                            formatDateTime(generation.settled_at)
                        }}</span>
                    </div>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
