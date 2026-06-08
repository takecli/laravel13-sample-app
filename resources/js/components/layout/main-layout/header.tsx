import {
    Box,
    Drawer,
    Flex,
    Heading,
    IconButton,
    Input,
    Portal,
    Spacer,
} from "@chakra-ui/react";
import { ColorModeButton } from "@/components/ui/color-mode";
import { Avatar } from "@/components/ui/avatar";
import { Sidebar } from "./sidebar";

function HamburgerIcon() {
    return (
        <svg width="1.25em" height="1.25em" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" aria-hidden>
            <path d="M3 6h18M3 12h18M3 18h18" />
        </svg>
    );
}

export function Header() {
    return (
        <Flex
            as="header"
            align="center"
            gap="3"
            h="14"
            px="4"
            bg="bg"
            borderBottomWidth="1px"
            borderColor="border"
            position="sticky"
            top="0"
            zIndex="docked"
        >
            {/* モバイル: ハンバーガー → Drawer に Sidebar を格納 */}
            <Box hideFrom="md">
                <Drawer.Root placement="start">
                    <Drawer.Trigger asChild>
                        <IconButton aria-label="メニューを開く" variant="ghost" size="sm">
                            <HamburgerIcon />
                        </IconButton>
                    </Drawer.Trigger>
                    <Portal>
                        <Drawer.Backdrop />
                        <Drawer.Positioner>
                            <Drawer.Content>
                                <Drawer.Body p="0">
                                    <Sidebar />
                                </Drawer.Body>
                            </Drawer.Content>
                        </Drawer.Positioner>
                    </Portal>
                </Drawer.Root>
            </Box>

            <Heading size="md" color="brand.fg">
                LearnHub
            </Heading>

            <Spacer />

            <Input placeholder="検索" size="sm" maxW="64" hideBelow="md" />
            <ColorModeButton />
            <Avatar name="User" size="sm" />
        </Flex>
    );
}
