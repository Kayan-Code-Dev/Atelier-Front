import { StrictMode } from "react";
import { createRoot } from "react-dom/client";
import "./index.css";
import "@/api/api-contants";
import App from "./App.tsx";
import {
  consumeTenantBootstrapFromHash,
  discardPersistedAuthIfBootstrapRedirect,
  syncApiBaseUrlWithAuthState,
} from "./lib/tenant-bootstrap";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { BrowserRouter } from "react-router";
import { useAuthStore } from "./zustand-stores/auth.store";

// Suppress [Violation] messages and Chrome Extension errors from console in development
if (import.meta.env.DEV) {
  // Suppress Chrome Extension async response errors
  const originalErrorHandler = window.onerror;
  window.onerror = (message, source, lineno, colno, error) => {
    const messageStr = String(message || '');
    if (
      messageStr.includes('message channel closed') ||
      messageStr.includes('asynchronous response') ||
      messageStr.includes('listener indicated')
    ) {
      return true; // Suppress the error
    }
    if (originalErrorHandler) {
      return originalErrorHandler(message, source, lineno, colno, error);
    }
    return false;
  };

  // Suppress unhandled promise rejections from extensions
  window.addEventListener('unhandledrejection', (event) => {
    const reasonStr = String(event.reason?.message || event.reason || '');
    if (
      reasonStr.includes('message channel closed') ||
      reasonStr.includes('asynchronous response') ||
      reasonStr.includes('listener indicated')
    ) {
      event.preventDefault();
      event.stopPropagation();
    }
  }, { capture: true });
  // Override console methods to filter out violation messages
  const originalWarn = console.warn;
  const originalError = console.error;
  const originalLog = console.log;
  const originalInfo = console.info;
  
  // Only suppress known Chrome extension / browser noise; do not hide app errors.
  const shouldSuppress = (message: string): boolean => {
    return (
      message.includes('message channel closed') ||
      message.includes('asynchronous response') ||
      message.includes('listener indicated')
    );
  };

  console.warn = (...args: any[]) => {
    const message = args.map(arg => String(arg)).join(' ');
    if (shouldSuppress(message)) return;
    originalWarn.apply(console, args);
  };

  // Never suppress console.error for generic "Violation" to avoid hiding real errors.
  console.error = (...args: any[]) => {
    const message = args.map(arg => String(arg)).join(' ');
    if (shouldSuppress(message)) return;
    originalError.apply(console, args);
  };
  
  console.log = (...args: any[]) => {
    const message = args.map(arg => String(arg)).join(' ');
    if (shouldSuppress(message)) return;
    originalLog.apply(console, args);
  };

  console.info = (...args: any[]) => {
    const message = args.map(arg => String(arg)).join(' ');
    if (shouldSuppress(message)) return;
    originalInfo.apply(console, args);
  };
  
  // Also suppress performance violations using PerformanceObserver
  if (typeof PerformanceObserver !== 'undefined') {
    try {
      const observer = new PerformanceObserver((list) => {
        // Suppress violation entries silently
        list.getEntries().forEach((entry) => {
          if (entry.entryType === 'measure' && entry.name.includes('Violation')) {
            // Suppress
          }
        });
      });
      observer.observe({ entryTypes: ['measure', 'mark'] });
    } catch {
      // PerformanceObserver not supported or error
    }
  }
}

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 1000 * 60 * 5, // 5 minutes
      retry: 2,
      refetchOnWindowFocus: false,
    },
    mutations: {
      retry: 0,
    },
  },
});

const rootEl = document.getElementById("root")!;
const root = createRoot(rootEl);

function renderApp() {
  root.render(
    <StrictMode>
      <BrowserRouter>
        <QueryClientProvider client={queryClient}>
          <App />
        </QueryClientProvider>
      </BrowserRouter>
    </StrictMode>,
  );
}

discardPersistedAuthIfBootstrapRedirect();

void Promise.resolve(useAuthStore.persist.rehydrate()).then(() => {
  consumeTenantBootstrapFromHash();
  syncApiBaseUrlWithAuthState();
  renderApp();
});
