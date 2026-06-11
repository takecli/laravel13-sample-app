import { ChakraProvider } from "@chakra-ui/react";
import type { PropsWithChildren } from "react";
import { system } from "@/theme";
import { ColorModeProvider } from "@/providers/color-mode-provider";
import { AuthProvider } from "@/context/auth-context";

/**
 * アプリ全体のプロバイダ。
 * - ChakraProvider: テーマ(system)を供給
 * - ColorModeProvider: next-themes 連携でライト/ダーク切替を供給
 */
export function RootProvider({ children }: PropsWithChildren) {
    return (
        <AuthProvider>
            <ChakraProvider value={system}>
                <ColorModeProvider defaultTheme="light" >{children}</ColorModeProvider>
            </ChakraProvider>
        </AuthProvider>
    );
}
