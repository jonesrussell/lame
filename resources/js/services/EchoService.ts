import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Make Pusher globally available
window.Pusher = Pusher;

// Extend window interface
declare global {
    interface Window {
        Echo: Echo<any>;
        Pusher: typeof Pusher;
    }
}

/**
 * Centralized Echo service following Singleton pattern
 * Handles WebSocket connection management and configuration
 */
class EchoService {
    private static instance: EchoService;
    private echo: Echo<any> | null = null;
    private isInitialized = false;

    private constructor() {}

    public static getInstance(): EchoService {
        if (!EchoService.instance) {
            EchoService.instance = new EchoService();
        }
        return EchoService.instance;
    }

    /**
     * Initialize Echo instance with proper configuration
     */
    public initialize(): Echo<any> {
        if (this.echo && this.isInitialized) {
            return this.echo;
        }

        if (window.Echo && this.isInitialized) {
            this.echo = window.Echo;
            return this.echo;
        }

        console.log('Initializing Echo with Reverb...', {
            key: import.meta.env.VITE_REVERB_APP_KEY,
            wsHost: import.meta.env.VITE_REVERB_HOST,
            wsPort: import.meta.env.VITE_REVERB_PORT,
            scheme: import.meta.env.VITE_REVERB_SCHEME,
        });

        this.echo = new Echo({
            broadcaster: 'reverb',
            key: import.meta.env.VITE_REVERB_APP_KEY,
            wsHost: import.meta.env.VITE_REVERB_HOST,
            wsPort: import.meta.env.VITE_REVERB_PORT,
            wssPort: import.meta.env.VITE_REVERB_PORT,
            forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
            enabledTransports: ['ws', 'wss'],
            wsPath: '/reverb',
            disableStats: true,
            authEndpoint: '/broadcasting/auth',
            auth: {
                headers: {
                    'X-CSRF-TOKEN': this.getCsrfToken(),
                },
            },
            cluster: false,
            encrypted: true,
        });

        window.Echo = this.echo;
        this.setupConnectionHandlers();
        this.isInitialized = true;

        return this.echo;
    }

    /**
     * Get the current Echo instance
     */
    public getEcho(): Echo<any> | null {
        return this.echo;
    }

    /**
     * Get connection state
     */
    public getConnectionState(): string {
        return this.echo?.connector?.pusher?.connection?.state || 'not_initialized';
    }

    /**
     * Get socket ID
     */
    public getSocketId(): string | null {
        return this.echo?.socketId() || null;
    }

    /**
     * Check if Echo is connected
     */
    public isConnected(): boolean {
        return this.getConnectionState() === 'connected';
    }

    /**
     * Disconnect and cleanup
     */
    public disconnect(): void {
        if (this.echo) {
            this.echo.disconnect();
        }
    }

    /**
     * Get CSRF token from meta tag
     */
    private getCsrfToken(): string {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    }

    /**
     * Setup connection event handlers with comprehensive logging
     */
    private setupConnectionHandlers(): void {
        if (!this.echo) return;

        const connection = this.echo.connector.pusher.connection;

        connection.bind('connecting', () => {
            const wsUrl = `${import.meta.env.VITE_REVERB_SCHEME === 'https' ? 'wss' : 'ws'}://${import.meta.env.VITE_REVERB_HOST}:${import.meta.env.VITE_REVERB_PORT}/reverb`;
            console.log('🔄 Connecting to WebSocket...');
            console.log('WebSocket URL:', wsUrl);
        });

        connection.bind('connected', () => {
            console.log('🔗 WebSocket Connected successfully');
            console.log('Socket ID:', this.getSocketId());
        });

        connection.bind('disconnected', () => {
            console.log('❌ WebSocket Disconnected');
        });

        connection.bind('failed', () => {
            console.error('🚨 WebSocket Connection FAILED');
            console.error('Check Reverb server status and nginx configuration');
        });

        connection.bind('error', (error: any) => {
            console.error('🚨 WebSocket Connection error:', error);
            console.error('Error details:', {
                code: error.code,
                reason: error.reason,
                wasClean: error.wasClean
            });
        });

        connection.bind('unavailable', () => {
            console.error('🚨 WebSocket transport unavailable');
        });
    }

    /**
     * Debug connection information
     */
    public debugConnection(): void {
        console.log('🔍 Echo Service Debug Info:');
        console.log('- Service initialized:', this.isInitialized);
        console.log('- Echo exists:', !!this.echo);
        console.log('- Connection state:', this.getConnectionState());
        console.log('- Socket ID:', this.getSocketId());
        console.log('- Is connected:', this.isConnected());
        
        if (this.echo) {
            console.log('- Channels:', Object.keys(this.echo.connector.channels));
        }
        
        console.log('- Environment vars:');
        console.log('  VITE_REVERB_APP_KEY:', import.meta.env.VITE_REVERB_APP_KEY);
        console.log('  VITE_REVERB_HOST:', import.meta.env.VITE_REVERB_HOST);
        console.log('  VITE_REVERB_PORT:', import.meta.env.VITE_REVERB_PORT);
        console.log('  VITE_REVERB_SCHEME:', import.meta.env.VITE_REVERB_SCHEME);
    }
}

export const echoService = EchoService.getInstance();
