import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import { VitePWA } from "vite-plugin-pwa";

export default defineConfig({
    plugins: [
        laravel({
            input: ["resources/js/app.js", "resources/css/app.css"],
            refresh: true,
        }),
        VitePWA({
            registerType: "autoUpdate",
            includeAssets: ["favicon.ico", "robots.txt"],
            manifest: {
                name: "Rapid Radio",
                short_name: "Radio",
                start_url: "/",
                display: "standalone",
                background_color: "#000000",
                theme_color: "#111827",
                icons: [
                    {
                        src: "/logo-192.png",
                        sizes: "192x192",
                        type: "image/png",
                    },
                    {
                        src: "/logo-512.png",
                        sizes: "512x512",
                        type: "image/png",
                    },
                ],
            },
        }),
    ],
});
