import { Spinner as ChakraSpinner } from "@chakra-ui/react";
import type { SpinnerProps as ChakraSpinnerProps } from "@chakra-ui/react";
import * as React from "react";

export interface SpinnerProps extends ChakraSpinnerProps {}

/** size 既定 "md" / 色を brand.solid に寄せた薄いラッパ。 */
export const Spinner = React.forwardRef<HTMLDivElement, SpinnerProps>(
    function Spinner({ size = "md", color = "brand.solid", ...rest }, ref) {
        return <ChakraSpinner ref={ref} size={size} color={color} {...rest} />;
    },
);
