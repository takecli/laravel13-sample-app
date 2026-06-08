import { Badge as ChakraBadge } from "@chakra-ui/react";
import type { BadgeProps } from "@chakra-ui/react";
import * as React from "react";

export type { BadgeProps };

/** 標準 Badge はそのまま再エクスポート。 */
export const Badge = ChakraBadge;

/** タグ表示用プリセット（丸み・brand の subtle 背景）。 */
export const TagBadge = React.forwardRef<HTMLSpanElement, BadgeProps>(
    function TagBadge(props, ref) {
        return (
            <ChakraBadge
                ref={ref}
                colorPalette="brand"
                variant="subtle"
                borderRadius="full"
                px="2.5"
                {...props}
            />
        );
    },
);
