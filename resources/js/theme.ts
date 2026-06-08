import { createSystem, defaultConfig, defineConfig } from "@chakra-ui/react";

/**
 * 学習資料プラットフォームのテーマ。
 * - 基盤は defaultConfig（標準の recipe / ライト・ダーク両対応の semantic token を含む）
 * - brand パレット（インディゴ寄りブルー）と役割ベースの semantic token を追加
 */
export const theme = defineConfig({
    globalCss: {
        "html, body": {
            bg: "bg",
            color: "fg",
        },
        a: {
            color: "brand.fg",
        },
        "*": {
            scrollMarginTop: "16",
        },
    },
    theme: {
        tokens: {
            colors: {
                brand: {
                    50: { value: "#eff6ff" },
                    100: { value: "#dbeafe" },
                    200: { value: "#bfdbfe" },
                    300: { value: "#93c5fd" },
                    400: { value: "#60a5fa" },
                    500: { value: "#3b82f6" },
                    600: { value: "#2563eb" },
                    700: { value: "#1d4ed8" },
                    800: { value: "#1e40af" },
                    900: { value: "#1e3a8a" },
                    950: { value: "#172554" },
                },
            },
        },
        semanticTokens: {
            colors: {
                // colorPalette="brand" を成立させるため solid/contrast/fg/muted/subtle/emphasized/focusRing を網羅
                brand: {
                    solid: { value: { base: "{colors.brand.500}", _dark: "{colors.brand.400}" } },
                    contrast: { value: { base: "white", _dark: "white" } },
                    fg: { value: { base: "{colors.brand.700}", _dark: "{colors.brand.300}" } },
                    muted: { value: { base: "{colors.brand.50}", _dark: "{colors.brand.900}" } },
                    subtle: { value: { base: "{colors.brand.100}", _dark: "{colors.brand.800}" } },
                    emphasized: { value: { base: "{colors.brand.200}", _dark: "{colors.brand.700}" } },
                    focusRing: { value: { base: "{colors.brand.500}", _dark: "{colors.brand.400}" } },
                },
                bg: { DEFAULT: { value: { _light: "white", _dark: "{colors.gray.950}" } } },
            },
        },
    },
});

export const system = createSystem(defaultConfig, theme);
