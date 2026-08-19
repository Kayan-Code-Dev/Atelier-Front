import { defineConfig } from "vite";
import react from "@vitejs/plugin-react";
import tailwindcss from "@tailwindcss/vite";
import path from "path"


// https://vite.dev/config/
export default defineConfig({
  plugins: [
    react({
      // Enable React production optimizations
      jsxRuntime: 'automatic',
    }),
    tailwindcss()
  ],
  resolve: {
    alias: {
      "@": path.resolve(__dirname, "./src"),
    },
  },
  server: {
    port: 5176,
    strictPort: true,
    proxy: {
      '/broadcasting': {
        target: 'http://127.0.0.1:8001',
        changeOrigin: true,
        secure: false,
      },
    },
  },
  build: {
    minify: "esbuild",
    sourcemap: false,
    rollupOptions: {
      output: {
        manualChunks(id) {
          if (!id.includes("node_modules")) {
            const normalized = id.replace(/\\/g, "/");
            const ordersNested = normalized.match(
              /\/src\/pages\/orders\/([^/]+)\//,
            );
            if (ordersNested) {
              return `page-orders-${ordersNested[1]}`;
            }
            const m = normalized.match(/\/src\/pages\/([^/]+)\//);
            if (m) return `page-${m[1]}`;
            return undefined;
          }
          if (
            id.includes("node_modules/react-dom") ||
            id.includes("node_modules/react/") ||
            id.includes("node_modules/react-router") ||
            id.includes("node_modules/scheduler/")
          ) {
            return "react-vendor";
          }
          if (id.includes("@tanstack/react-query")) return "query-vendor";
          if (id.includes("echarts")) return "echarts-vendor";
          if (id.includes("laravel-echo") || id.includes("pusher-js")) {
            return "echo-pusher";
          }
          if (id.includes("@radix-ui")) return "radix-ui-vendor";
          if (id.includes("lucide-react")) return "lucide-vendor";
          if (id.includes("date-fns")) return "date-fns-vendor";
          if (id.includes("zod") || id.includes("@hookform")) {
            return "form-vendor";
          }
          if (id.includes("axios")) return "axios-vendor";
          if (id.includes("sonner")) return "sonner-vendor";
          if (id.includes("cmdk")) return "cmdk-vendor";
          return "vendor";
        },
      },
    },
    chunkSizeWarningLimit: 1000,
  },
  // Ensure production mode is set correctly
  define: {
    'process.env.NODE_ENV': JSON.stringify(process.env.NODE_ENV || 'development'),
  },
});
