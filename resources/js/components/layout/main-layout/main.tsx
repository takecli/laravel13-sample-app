import { Box, Container, Flex } from "@chakra-ui/react";
import type { PropsWithChildren } from "react";
import { Header } from "./header";
import { Sidebar } from "./sidebar";
import { Footer } from "./footer";

/**
 * アプリ共通レイアウト。ヘッダー固定 + 左サイドバー + 主コンテンツ + フッター。
 * children を受け取る疎結合な見た目専用コンポーネント。
 * ルーティング側で <MainLayout><Outlet /></MainLayout> のように差し込む。
 */
export function MainLayout({ children }: PropsWithChildren) {
    return (
        <Flex direction="column" minH="100dvh">
            <Header />
            <Flex flex="1">
                <Box
                    as="aside"
                    w="60"
                    flexShrink="0"
                    borderRightWidth="1px"
                    borderColor="border"
                    hideBelow="md"
                    position="sticky"
                    top="14"
                    alignSelf="flex-start"
                    h="calc(100dvh - 3.5rem)"
                    overflowY="auto"
                >
                    <Sidebar />
                </Box>
                <Flex direction="column" flex="1" minW="0">
                    <Box as="main" flex="1">
                        <Container maxW="3xl" py="8">
                            {children}
                        </Container>
                    </Box>
                    <Footer />
                </Flex>
            </Flex>
        </Flex>
    );
}
