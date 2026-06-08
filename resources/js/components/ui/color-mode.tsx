import type { IconButtonProps, SpanProps } from "@chakra-ui/react";
import { ClientOnly, IconButton, Skeleton, Span } from "@chakra-ui/react";
import { useTheme } from "next-themes";
import * as React from "react";

/**
 * Chakra UI v3 公式 color-mode snippet 準拠。
 * Provider は @/providers/color-mode-provider に分離し、ここでは hook / Button / ユーティリティを提供する。
 * アイコンは react-icons 依存を避けるためインライン SVG で実装。
 */

export type ColorMode = "light" | "dark";

export interface UseColorModeReturn {
    colorMode: ColorMode;
    setColorMode: (colorMode: ColorMode) => void;
    toggleColorMode: () => void;
}

export function useColorMode(): UseColorModeReturn {
    const { resolvedTheme, setTheme, forcedTheme } = useTheme();
    const colorMode = forcedTheme || resolvedTheme;
    const toggleColorMode = () => {
        setTheme(resolvedTheme === "dark" ? "light" : "dark");
    };
    return {
        colorMode: colorMode as ColorMode,
        setColorMode: setTheme,
        toggleColorMode,
    };
}

export function useColorModeValue<T>(light: T, dark: T): T {
    const { colorMode } = useColorMode();
    return colorMode === "dark" ? dark : light;
}

function SunIcon() {
    return (
        <svg width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
            <circle cx="12" cy="12" r="4" />
            <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41" />
        </svg>
    );
}

function MoonIcon() {
    return (
        <svg width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z" />
        </svg>
    );
}

export function ColorModeIcon() {
    const { colorMode } = useColorMode();
    return colorMode === "dark" ? <MoonIcon /> : <SunIcon />;
}

type ColorModeButtonProps = Omit<IconButtonProps, "aria-label">;

export const ColorModeButton = React.forwardRef<HTMLButtonElement, ColorModeButtonProps>(
    function ColorModeButton(props, ref) {
        const { toggleColorMode } = useColorMode();
        return (
            <ClientOnly fallback={<Skeleton boxSize="8" />}>
                <IconButton
                    onClick={toggleColorMode}
                    variant="ghost"
                    aria-label="カラーモードを切り替え"
                    size="sm"
                    ref={ref}
                    {...props}
                >
                    <ColorModeIcon />
                </IconButton>
            </ClientOnly>
        );
    },
);

export const LightMode = React.forwardRef<HTMLSpanElement, SpanProps>(function LightMode(props, ref) {
    return (
        <Span
            color="fg"
            display="contents"
            className="chakra-theme light"
            colorPalette="gray"
            colorScheme="light"
            ref={ref}
            {...props}
        />
    );
});

export const DarkMode = React.forwardRef<HTMLSpanElement, SpanProps>(function DarkMode(props, ref) {
    return (
        <Span
            color="fg"
            display="contents"
            className="chakra-theme dark"
            colorPalette="gray"
            colorScheme="dark"
            ref={ref}
            {...props}
        />
    );
});
