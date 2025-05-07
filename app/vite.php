<?php
function vite_asset(string $entry): string
{
    $defaultPublicDir = "public";
    $defaultBuildDir = "build";
    $fallbackManifestPath = __DIR__ . "/$defaultPublicDir/$defaultBuildDir/manifest.json";

    if (!file_exists($fallbackManifestPath)) {
        throw new RuntimeException("Vite manifest not found at: $fallbackManifestPath");
    }

    $manifest = json_decode(file_get_contents($fallbackManifestPath), true);
    if (!is_array($manifest)) {
        throw new RuntimeException("Invalid JSON in Vite manifest.");
    }

    $config = $manifest["__config"] ?? [];
    $publicDirectoryName = $config["publicDirectoryName"] ?? $defaultPublicDir;
    $buildDirectoryName = $config["buildDirectoryName"] ?? $defaultBuildDir;
    
    $manifestEntry = $manifest[$entry];
    if (!isset($manifestEntry)) {
        throw new RuntimeException("Vite manifest entry \"$entry\" not found in manifest.");
    }

    $manifestEntryFile = $manifestEntry["file"];
    return rtrim($buildDirectoryName, "/") . ($manifestEntryFile ? "/" . ltrim($manifestEntryFile, "/") : "");
}
