import { Avatar as ChakraAvatar } from "@chakra-ui/react";
import type { AvatarRootProps } from "@chakra-ui/react";
import * as React from "react";

export interface AvatarProps extends AvatarRootProps {
    name?: string;
    src?: string;
}

/**
 * name からイニシャルを生成して fallback 表示する合成 Avatar。
 * 画像があれば src を優先表示。
 */
export const Avatar = React.forwardRef<HTMLDivElement, AvatarProps>(
    function Avatar({ name, src, ...rest }, ref) {
        return (
            <ChakraAvatar.Root ref={ref} {...rest}>
                <ChakraAvatar.Fallback name={name} />
                {src ? <ChakraAvatar.Image src={src} alt={name} /> : null}
            </ChakraAvatar.Root>
        );
    },
);
