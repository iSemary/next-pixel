import { AlertCircle, CheckCircle2, Clock, Loader2 } from '@lucide/vue';
import type { Component } from 'vue';
import { formatUsdEstimate } from '@/composables/usePricingEstimate';
import type { GenerationItem } from '@/types/generation';

export interface GenerationStatusMeta {
    label: string;
    badgeClass: string;
    icon: Component;
    previewIconClass: string;
    placeholderText: string;
    spin?: boolean;
}

export const GENERATION_STATUS_META: Record<
    GenerationItem['status'],
    GenerationStatusMeta
> = {
    queued: {
        label: 'Queued',
        badgeClass: 'bg-amber-500/10 text-amber-600 dark:text-amber-400',
        icon: Clock,
        previewIconClass: 'text-amber-500',
        placeholderText: 'Waiting in queue...',
    },
    processing: {
        label: 'Processing',
        badgeClass: 'bg-blue-500/10 text-blue-600 dark:text-blue-400',
        icon: Loader2,
        previewIconClass: 'text-primary',
        placeholderText: 'Rendering image...',
        spin: true,
    },
    succeeded: {
        label: 'Succeeded',
        badgeClass: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400',
        icon: CheckCircle2,
        previewIconClass: 'text-emerald-500',
        placeholderText: 'Ready',
    },
    failed: {
        label: 'Failed',
        badgeClass: 'bg-red-500/10 text-red-600 dark:text-red-400',
        icon: AlertCircle,
        previewIconClass: 'text-red-600 dark:text-red-400',
        placeholderText: 'Generation failed (free)',
    },
};

const ERROR_CODE_LABELS: Record<string, string> = {
    content_policy_violation: 'Blocked by content policy',
    generation_failed: 'Generation failed',
    submission_failed: 'Submission failed',
    rate_limit_exceeded: 'Rate limit exceeded',
    daily_limit_exceeded: 'Daily limit exceeded',
    concurrency_limit_exceeded: 'Concurrency limit reached',
    overloaded: 'Service temporarily overloaded',
    invalid_request: 'Invalid generation request',
    idempotency_key_reused: 'Duplicate request key',
};

export function formatGenerationErrorTitle(
    errorCode: string | null | undefined,
): string {
    if (!errorCode) {
        return 'Generation failed';
    }

    const normalized = errorCode.trim().toLowerCase();
    if (normalized in ERROR_CODE_LABELS) {
        return ERROR_CODE_LABELS[normalized];
    }

    const words = normalized.replace(/[_-]+/g, ' ').trim();
    if (!words) {
        return 'Generation failed';
    }

    return words.charAt(0).toUpperCase() + words.slice(1);
}

export function formatGenerationUsd(
    cost: string | null | undefined,
    prefix = '',
): string | null {
    if (cost === null || cost === undefined || cost === '') {
        return null;
    }

    const numeric = parseFloat(cost);
    if (isNaN(numeric)) {
        return null;
    }

    return `${prefix}${formatUsdEstimate(numeric)}`;
}

export function formatExactUsd(cost: string | null | undefined): string {
    if (cost === null || cost === undefined || cost === '') {
        return '—';
    }

    const numeric = parseFloat(cost);
    if (isNaN(numeric)) {
        return '—';
    }

    if (numeric === 0) {
        return '$0.0000';
    }

    return `$${numeric.toFixed(6).replace(/0+$/, '').replace(/\.$/, '')}`;
}
