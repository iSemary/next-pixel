export interface AdminReportFilters {
    start_date: string;
    end_date: string;
}

export interface TokenTotalsBreakdown {
    input_text: number;
    input_image: number;
    output: number;
    reasoning: number;
    total: number;
}

export interface AdminReportSummary {
    credit_spend: string;
    local_provider_cost: string;
    pixelforge_provider_cost: string | null;
    provider_cost: string;
    profit: string;
    margin_percent: string;
    discrepancy: string | null;
    matches_pixelforge: boolean | null;
    succeeded_generations: number;
    failed_generations: number;
    pixelforge_generations: number | null;
    local_tokens: TokenTotalsBreakdown;
    pixelforge_tokens: TokenTotalsBreakdown | null;
    pixelforge_available: boolean;
    pixelforge_error: string | null;
}

export interface AdminReportDailyRow {
    date: string;
    succeeded_generations: number;
    failed_generations: number;
    credit_spend: string;
    local_provider_cost: string;
    pixelforge_cost: string | null;
    pixelforge_generations: number | null;
    provider_cost: string;
    profit: string;
    discrepancy: string | null;
    matches_pixelforge: boolean | null;
    local_total_tokens: number;
    pixelforge_total_tokens: number | null;
    has_activity: boolean;
}

export interface AdminReportUserRow {
    user_id: number | null;
    name: string;
    email: string;
    credit_balance: string;
    succeeded_generations: number;
    failed_generations: number;
    credit_spend: string;
    provider_cost: string;
    profit: string;
    total_tokens: number;
}

export interface AdminReportPageProps {
    filters: AdminReportFilters;
    summary: AdminReportSummary;
    daily: AdminReportDailyRow[];
    by_user: AdminReportUserRow[];
    synced_at: string;
}
