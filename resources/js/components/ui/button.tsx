import { Button as ChakraButton } from "@chakra-ui/react";
import type { ButtonProps as ChakraButtonProps } from "@chakra-ui/react";
import * as React from "react";

export interface ButtonProps extends ChakraButtonProps {}

/**
 * プロジェクト既定の Button。colorPalette を "brand" 既定にする以外は Chakra Button と同一。
 * loading / loadingText は Chakra v3 が標準サポート。
 */
export const Button = React.forwardRef<HTMLButtonElement, ButtonProps>(
    function Button({ colorPalette = "brand", ...rest }, ref) {
        return <ChakraButton ref={ref} colorPalette={colorPalette} {...rest} />;
    },
);
