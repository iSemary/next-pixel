export function getXsrfToken(): string | null {
    if (typeof document === 'undefined') {
        return null;
    }

    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);

    return match ? decodeURIComponent(match[1]) : null;
}

export interface RequestJsonOptions {
    headers?: Record<string, string>;
    signal?: AbortSignal;
    defaultErrorMessage?: string;
}

async function requestJson<TResponse>(
    method: 'GET' | 'POST',
    url: string,
    body?: Record<string, unknown> | FormData,
    options: RequestJsonOptions = {},
): Promise<TResponse> {
    const xsrfToken = getXsrfToken();
    const isFormData =
        typeof FormData !== 'undefined' && body instanceof FormData;

    const headers: Record<string, string> = {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        ...options.headers,
    };

    if (method === 'POST' && !isFormData) {
        headers['Content-Type'] = 'application/json';
    }

    if (xsrfToken) {
        headers['X-XSRF-TOKEN'] = xsrfToken;
    }

    const fetchOptions: RequestInit = {
        method,
        headers,
        credentials: 'same-origin',
        signal: options.signal,
    };

    if (method === 'POST' && body) {
        fetchOptions.body = isFormData ? body : JSON.stringify(body);
    }

    const response = await fetch(url, fetchOptions);

    const data = await response.json().catch(() => null);

    if (!response.ok) {
        if (response.status === 422 && data?.errors) {
            const firstError = Object.values(data.errors)[0] as
                | string[]
                | undefined;
            throw new Error(
                firstError?.[0] ||
                    options.defaultErrorMessage ||
                    'Validation error.',
            );
        }

        throw new Error(
            data?.message ||
                `${options.defaultErrorMessage || 'Request failed'} (${response.status})`,
        );
    }

    return data as TResponse;
}

export function getJson<TResponse>(
    url: string,
    options: RequestJsonOptions = {},
): Promise<TResponse> {
    return requestJson<TResponse>('GET', url, undefined, options);
}

/**
 * Send a same-origin JSON POST request with Laravel XSRF headers and standardized error extraction.
 */
export function postJson<TResponse>(
    url: string,
    body: Record<string, unknown>,
    options: RequestJsonOptions = {},
): Promise<TResponse> {
    return requestJson<TResponse>('POST', url, body, options);
}

/**
 * Send a same-origin multipart FormData POST request (e.g. file upload).
 */
export function postFormData<TResponse>(
    url: string,
    formData: FormData,
    options: RequestJsonOptions = {},
): Promise<TResponse> {
    return requestJson<TResponse>('POST', url, formData, options);
}
