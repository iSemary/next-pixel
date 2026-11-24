export interface ImageSizeOption {
    id: string;
    value: string;
    label: string;
    aspectRatio: string;
    name: string;
}

export interface ImageQualityOption {
    id: string;
    value: string;
    name: string;
    description: string;
}

export interface TokenCategoryEstimate {
    category: string;
    label: string;
    estimated_tokens: number;
    unit_rate_per_1m: string;
    estimated_cost_usd: string;
    details?: Record<string, unknown>;
}

export interface PricingEstimationResult {
    currency: string;
    total_tokens: number;
    estimated_provider_cost: string;
    markup_rate: string;
    markup_percentage: string;
    markup_amount: string;
    estimated_user_cost: string;
    categories: {
        input_text?: TokenCategoryEstimate;
        input_image?: TokenCategoryEstimate;
        output?: TokenCategoryEstimate;
        reasoning?: TokenCategoryEstimate;
        [key: string]: TokenCategoryEstimate | undefined;
    };
    pricing_snapshot: Record<string, unknown>;
    assumptions?: Record<string, unknown>;
}

export interface GenerationFormState {
    prompt: string;
    size: string;
    quality: string;
    aspectRatio?: string;
    resolution?: string;
    numGenerations?: number;
    referenceImages: string[];
}

export interface GenerationItem {
    id: number;
    provider_generation_id: string | null;
    idempotency_key: string;
    prompt: string;
    size: string;
    quality: string;
    reference_images: string[];
    metadata: Record<string, string>;
    status: 'queued' | 'processing' | 'succeeded' | 'failed';
    provider_status: string | null;
    image_url: string | null;
    image_url_expires_at: string | null;
    error_code: string | null;
    error_message: string | null;
    submitted_at: string | null;
    started_at: string | null;
    completed_at: string | null;
    settled_at: string | null;
    is_settled: boolean;
    estimated_tokens: number | null;
    estimated_input_text_tokens: number | null;
    estimated_input_image_tokens: number | null;
    estimated_output_tokens: number | null;
    estimated_reasoning_tokens_min: number | null;
    estimated_reasoning_tokens_max: number | null;
    estimated_total_tokens_min: number | null;
    estimated_total_tokens_max: number | null;
    estimated_provider_cost: string | null;
    estimated_user_cost: string | null;
    actual_tokens: number | null;
    actual_input_text_tokens: number | null;
    actual_input_image_tokens: number | null;
    actual_output_tokens: number | null;
    actual_reasoning_tokens: number | null;
    actual_total_tokens: number | null;
    actual_provider_cost: string | null;
    actual_user_cost: string | null;
    applied_markup_rate: string | null;
    created_at: string;
    updated_at: string;
}

export interface GenerationSubmissionResponse {
    generations: GenerationItem[];
    is_idempotent_replay: boolean;
    credit_balance?: string;
    message: string;
}

export interface GenerationSummaryStats {
    total_count: number;
    succeeded_count: number;
    failed_count: number;
    in_flight_count: number;
    total_estimated_cost: string;
    total_actual_cost: string;
}

export interface GenerationPollResponse {
    generations: GenerationItem[];
    summary?: GenerationSummaryStats;
    credit_balance?: string;
}

export interface LibraryFilters {
    status: string;
    quality: string;
    size: string;
    search: string;
}

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface PaginatedGenerations {
    data: GenerationItem[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    prev_page_url: string | null;
    next_page_url: string | null;
    links: PaginationLink[];
}
