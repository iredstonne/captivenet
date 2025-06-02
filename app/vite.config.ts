import { defineConfig } from "vite"
import tailwindcss from "@tailwindcss/vite"
import compile from "./vite-plugin.ts"

export default defineConfig({
    plugins: [
        tailwindcss(),
        compile([
            "resources/styles/index.css",
            "resources/scripts/index.ts",
            "resources/scripts/session.ts",
        ])
    ]
})
