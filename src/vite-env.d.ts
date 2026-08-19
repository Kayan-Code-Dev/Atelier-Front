/// <reference types="vite/client" />

interface ImportMetaEnv {
  readonly VITE_TENANT_RELATIVE_API?: string;
}

declare global {
  interface Window {
    Pusher: typeof import('pusher-js');
    Echo: typeof import('laravel-echo');
  }
}

export {};
