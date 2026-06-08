import { Box, Stack } from "@chakra-ui/react";
import { Link, useRouterState } from "@tanstack/react-router";

/** ナビ項目。/notes 等は対応ルート未実装のためリンク先プレースホルダ。 */
const navItems = [
    { label: "ホーム", to: "/" },
    { label: "記事", to: "/notes" },
    { label: "チーム", to: "/teams" },
    { label: "タグ", to: "/tags" },
];

export function Sidebar() {
    const pathname = useRouterState({ select: (s) => s.location.pathname });

    return (
        <Box as="nav" p="3">
            <Stack gap="1">
                {navItems.map((item) => {
                    const active =
                        item.to === "/" ? pathname === "/" : pathname.startsWith(item.to);
                    return (
                        <Link key={item.to} to={item.to}>
                            <Box
                                px="3"
                                py="2"
                                borderRadius="md"
                                fontWeight="medium"
                                color={active ? "brand.fg" : "fg.muted"}
                                bg={active ? "brand.muted" : "transparent"}
                                _hover={{ bg: active ? "brand.muted" : "bg.muted" }}
                            >
                                {item.label}
                            </Box>
                        </Link>
                    );
                })}
            </Stack>
        </Box>
    );
}
