import { formatExactUsd } from '@/components/generation/generationStatusMeta';

export function formatSignedDelta(value: string | null): string {
    if (value === null) {
        return '—';
    }

    const num = parseFloat(value);
    if (isNaN(num) || num === 0) {
        return '$0.0000';
    }

    const absFormatted = formatExactUsd(String(Math.abs(num)));

    return num > 0 ? `+${absFormatted}` : `-${absFormatted}`;
}

export function formatDateUtc(isoDate: string): string {
    const [y, m, d] = isoDate.split('-').map(Number);
    if (!y || !m || !d) {
        return isoDate;
    }

    return new Intl.DateTimeFormat('en-US', {
        timeZone: 'UTC',
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    }).format(new Date(Date.UTC(y, m - 1, d)));
}
