import { Textarea as ChakraTextarea } from "@chakra-ui/react";
import type { TextareaProps as ChakraTextareaProps } from "@chakra-ui/react";
import * as React from "react";

export interface TextareaProps extends ChakraTextareaProps {}

/** size 既定を "md" に統一した薄いラッパ。 */
export const Textarea = React.forwardRef<HTMLTextAreaElement, TextareaProps>(
    function Textarea({ size = "md", ...rest }, ref) {
        return <ChakraTextarea ref={ref} size={size} {...rest} />;
    },
);
