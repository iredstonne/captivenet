import { Plugin, ResolvedConfig, UserConfig } from "vite"
import { InputOption } from "rollup"
import path from "path"
import fs from "fs-extra"

type PluginConfig = {
    input: InputOption,
    publicDirectoryName?: string,
    buildDirectoryName?: string
}

class VitePluginError extends Error {
    constructor(message: string) {
        super(message)
        this.name = "VitePluginError"
        Object.setPrototypeOf(this, new.target.prototype)
    }
}

const resolvePluginConfig = (config?: string | string[] | PluginConfig | undefined): Required<PluginConfig> => {
    if(typeof config === "undefined") {
        throw new VitePluginError("Missing configuration")
    }
    if(typeof config === "string" || Array.isArray(config)) {
        config = { input: config }
    }

    if(typeof config.input === "undefined") {
        throw new VitePluginError("Missing input configuration")
    }

    if (typeof config.publicDirectoryName === "string") {
        config.publicDirectoryName = config.publicDirectoryName.trim()
            .replace(/^\/+|\/+$/g, "")
        if(config.publicDirectoryName === "") {
            throw new VitePluginError("Public directory name must be non-empty. Leading or trailing slashes are not allowed.")
        }
    }

    if (typeof config.buildDirectoryName === "string") {
        config.buildDirectoryName = config.buildDirectoryName.trim()
            .replace(/^\/+|\/+$/g, "")
        if(config.buildDirectoryName === "") {
            throw new VitePluginError("Build directory name must be non-empty. Leading or trailing slashes are not allowed.")
        }
    }

    return {
        input: config.input,
        publicDirectoryName: config.publicDirectoryName ?? "public",
        buildDirectoryName: config.buildDirectoryName ?? "build"
    }
}

const createPlugin = (config: Required<PluginConfig>): Plugin => {
    let resolvedUserConfig: ResolvedConfig
    return {
        name: "php-vite-plugin",
        enforce: "post",
        config(currentUserConfig, { command }): UserConfig {
            if(command === "serve") {
                throw new VitePluginError("This plugin does support `vite dev`. Only `vite build` is allowed.")
            }
            return {
                base: currentUserConfig.base ?? "/" + config.buildDirectoryName + "/",
                publicDir: currentUserConfig.publicDir ?? "/" + config.publicDirectoryName + "/",
                build: {
                    manifest: currentUserConfig.build?.manifest ?? "manifest.json",
                    outDir: currentUserConfig.build?.outDir ?? path.join(config.publicDirectoryName, config.buildDirectoryName),
                    rollupOptions: {
                        input: currentUserConfig.build?.rollupOptions?.input ?? config.input
                    }
                }
            }
        },
        configResolved(_resolvedUserConfig) {
            resolvedUserConfig = _resolvedUserConfig
        },
        closeBundle() {
            const manifestPath = path.join(resolvedUserConfig.build.outDir, "manifest.json")
            if(fs.pathExistsSync(manifestPath)) {
                const manifest = fs.readJsonSync(manifestPath)
                manifest.__config = {
                    publicDirectoryName: config.publicDirectoryName,
                    buildDirectoryName: config.buildDirectoryName,
                }
                fs.writeJsonSync(manifestPath, manifest, {
                    encoding: "UTF-8",
                    spaces: 2
                })
            }
        }
    }
}

export default (config: string | string[] | PluginConfig | undefined): Plugin => {
    return createPlugin(resolvePluginConfig(config))
}
