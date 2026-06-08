import { Box, Text } from "@chakra-ui/react";

export function Footer() {
    return (
        <Box as="footer" borderTopWidth="1px" borderColor="border" px="6" py="4">
            <Text fontSize="sm" color="fg.muted">
                © 2026 学習資料プラットフォーム
            </Text>
        </Box>
    );
}
