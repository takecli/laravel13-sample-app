import { EmptyState as ChakraEmptyState, VStack } from "@chakra-ui/react";
import type { EmptyStateRootProps } from "@chakra-ui/react";
import * as React from "react";

export interface EmptyStateProps extends EmptyStateRootProps {
    title: string;
    description?: string;
    icon?: React.ReactNode;
}

/**
 * 「記事がありません」等の空状態表示。Chakra v3 公式 empty-state snippet 準拠。
 * children にはアクション（ボタン等）を渡せる。
 */
export const EmptyState = React.forwardRef<HTMLDivElement, EmptyStateProps>(
    function EmptyState({ title, description, icon, children, ...rest }, ref) {
        return (
            <ChakraEmptyState.Root ref={ref} {...rest}>
                <ChakraEmptyState.Content>
                    {icon ? <ChakraEmptyState.Indicator>{icon}</ChakraEmptyState.Indicator> : null}
                    <VStack textAlign="center">
                        <ChakraEmptyState.Title>{title}</ChakraEmptyState.Title>
                        {description ? (
                            <ChakraEmptyState.Description>{description}</ChakraEmptyState.Description>
                        ) : null}
                    </VStack>
                    {children}
                </ChakraEmptyState.Content>
            </ChakraEmptyState.Root>
        );
    },
);
