import { ThemeProvider } from "next-themes";
import type { ThemeProviderProps } from "next-themes";

export interface ColorModeProviderProps extends ThemeProviderProps {}

/**
 * next-themes 連携でライト/ダークのカラーモードを供給する Provider。
 * Button / hook などの UI ユーティリティは @/component/ui/color-mode 側に置く。
 */
export function ColorModeProvider(props: ColorModeProviderProps) {
    return <ThemeProvider attribute="class" disableTransitionOnChange {...props} />;
}
