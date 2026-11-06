import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import vue from "@vitejs/plugin-vue";
import path from "path";

const ddevUrl = process.env.DDEV_PRIMARY_URL;

export default defineConfig({
    // Run inside DDEV: listen on all interfaces and serve HMR via the DDEV https URL
    server: ddevUrl
        ? {
              host: "0.0.0.0",
              port: 5173,
              strictPort: true,
              // laravel-vite-plugin builds public/hot from the hmr settings
              hmr: { protocol: "wss", host: new URL(ddevUrl).hostname, clientPort: 5173 },
              origin: `${ddevUrl.replace(/:\d+$/, "")}:5173`,
              cors: { origin: /https?:\/\/([A-Za-z0-9\-.]+)?(\.ddev\.site)(?::\d+)?$/ },
          }
        : undefined,
    plugins: [
        vue(),
        laravel({
            input: [
                "resources/css/app.css",
                "resources/css/project.css",
                "resources/js/app.ts",
            ],
            refresh: true,
        }),
    ],
    resolve: {
        alias: {
            "@": path.resolve(import.meta.dirname, "./resources/js"),
            "@book": path.resolve(import.meta.dirname, "./packages/soranoiseki/book-group/src/Resources/js"),
        },
    },
});
