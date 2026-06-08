import { Input as ChakraInput } from "@chakra-ui/react";
import type { InputProps as ChakraInputProps } from "@chakra-ui/react";
import * as React from "react";

export interface InputProps extends ChakraInputProps {}

/** size 既定を "md" に統一した薄いラッパ。 */
export const Input = React.forwardRef<HTMLInputElement, InputProps>(
    function Input({ size = "md", ...rest }, ref) {
        return <ChakraInput ref={ref} size={size} {...rest} />;
    },
);
