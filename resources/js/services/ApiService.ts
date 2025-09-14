/**
 * Centralized API service for HTTP requests
 * Handles authentication, error handling, and request configuration
 */
class ApiService {
    private static instance: ApiService;

    private constructor() {}

    public static getInstance(): ApiService {
        if (!ApiService.instance) {
            ApiService.instance = new ApiService();
        }
        return ApiService.instance;
    }

    /**
     * Get CSRF token from meta tag
     */
    private getCsrfToken(): string {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    }

    /**
     * Make authenticated API request
     */
    public async makeRequest<T = any>(url: string, options: RequestInit = {}): Promise<T> {
        const response = await fetch(url, {
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': this.getCsrfToken(),
                ...options.headers,
            },
            ...options,
        });

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.message || `Request failed: ${response.status}`);
        }

        return response.json();
    }

    /**
     * GET request
     */
    public async get<T = any>(url: string, options: RequestInit = {}): Promise<T> {
        return this.makeRequest<T>(url, {
            method: 'GET',
            ...options,
        });
    }

    /**
     * POST request
     */
    public async post<T = any>(url: string, data: any = {}, options: RequestInit = {}): Promise<T> {
        return this.makeRequest<T>(url, {
            method: 'POST',
            body: JSON.stringify(data),
            ...options,
        });
    }

    /**
     * PATCH request
     */
    public async patch<T = any>(url: string, data: any = {}, options: RequestInit = {}): Promise<T> {
        return this.makeRequest<T>(url, {
            method: 'PATCH',
            body: JSON.stringify(data),
            ...options,
        });
    }

    /**
     * DELETE request
     */
    public async delete<T = any>(url: string, options: RequestInit = {}): Promise<T> {
        return this.makeRequest<T>(url, {
            method: 'DELETE',
            ...options,
        });
    }
}

export const apiService = ApiService.getInstance();
