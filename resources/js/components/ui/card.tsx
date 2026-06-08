import { Card as ChakraCard } from "@chakra-ui/react";
import type { CardRootProps } from "@chakra-ui/react";
import * as React from "react";

const Root = React.forwardRef<HTMLDivElement, CardRootProps>(
    function CardRoot({ variant = "outline", ...rest }, ref) {
        return <ChakraCard.Root ref={ref} variant={variant} {...rest} />;
    },
);

/**
 * Card 合成。Root は variant="outline" 既定（テーマの border に追従）。
 * 使い方: <Card.Root><Card.Header/><Card.Body/><Card.Footer/></Card.Root>
 */
export const Card = {
    Root,
    Header: ChakraCard.Header,
    Body: ChakraCard.Body,
    Footer: ChakraCard.Footer,
    Title: ChakraCard.Title,
    Description: ChakraCard.Description,
};
