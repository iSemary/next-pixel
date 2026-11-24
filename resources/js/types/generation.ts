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
