import { useState } from "react"
import {
    ButtonGroup,
    Flex,
    HStack,
    Heading,
    IconButton,
    Pagination,
    Spacer,
    Stack,
    Text,
} from "@chakra-ui/react"
import { MainLayout } from "@/components/layout/main-layout"
import { Avatar, Badge, Card, EmptyState, TagBadge } from "@/components/ui"
import { mockNotes, type MockNote } from "@/mocks/notes"

const PAGE_SIZE = 3

const dateFormatter = new Intl.DateTimeFormat("ja-JP", {
    year: "numeric",
    month: "2-digit",
    day: "2-digit",
})

function ChevronLeftIcon() {
    return (
        <svg width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
            <path d="M15 18l-6-6 6-6" />
        </svg>
    )
}

function ChevronRightIcon() {
    return (
        <svg width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
            <path d="M9 18l6-6-6-6" />
        </svg>
    )
}

function NoteCard({ note }: { note: MockNote }) {
    return (
        <Card.Root>
            <Card.Body gap="3">
                <HStack gap="2">
                    <Badge colorPalette={note.status === "published" ? "green" : "gray"}>
                        {note.status === "published" ? "公開" : "下書き"}
                    </Badge>
                    <Text fontSize="sm" color="fg.muted">
                        {note.team.name}
                    </Text>
                    <Spacer />
                    <Text fontSize="sm" color="fg.muted">
                        {dateFormatter.format(new Date(note.publishedAt))}
                    </Text>
                </HStack>

                <Stack gap="1">
                    <Heading size="md">{note.title}</Heading>
                    <Text color="fg.muted" lineClamp={2}>
                        {note.excerpt}
                    </Text>
                </Stack>

                <HStack gap="2" wrap="wrap">
                    {note.tags.map((tag) => (
                        <TagBadge key={tag}>{tag}</TagBadge>
                    ))}
                </HStack>

                <HStack gap="3" pt="1">
                    <HStack gap="2">
                        <Avatar name={note.author.name} size="xs" />
                        <Text fontSize="sm">{note.author.name}</Text>
                    </HStack>
                    <Spacer />
                    <HStack gap="4" color="fg.muted" fontSize="sm">
                        <Text>♥ {note.likeCount}</Text>
                        <Text>💬 {note.commentCount}</Text>
                    </HStack>
                </HStack>
            </Card.Body>
        </Card.Root>
    )
}

/** ホーム：所属しているチームの投稿一覧（ページング付き）。 */
export function HomePage() {
    const [page, setPage] = useState(1)

    // 公開済みを新しい順に表示（API 接続前はモックを使用）
    const notes = [...mockNotes]
        .filter((n) => n.status === "published")
        .sort((a, b) => b.publishedAt.localeCompare(a.publishedAt))

    const total = notes.length
    const start = (page - 1) * PAGE_SIZE
    const visibleNotes = notes.slice(start, start + PAGE_SIZE)

    return (
        <MainLayout>
            <Stack gap="6">
                <Flex align="baseline" gap="2">
                    <Heading size="xl">所属チームの投稿</Heading>
                    <Text color="fg.muted">{total} 件</Text>
                </Flex>

                {total === 0 ? (
                    <Card.Root>
                        <Card.Body>
                            <EmptyState
                                title="まだ投稿がありません"
                                description="所属チームに学習資料が投稿されるとここに表示されます。"
                            />
                        </Card.Body>
                    </Card.Root>
                ) : (
                    <Stack gap="6">
                        <Stack gap="4">
                            {visibleNotes.map((note) => (
                                <NoteCard key={note.id} note={note} />
                            ))}
                        </Stack>

                        <Pagination.Root
                            count={total}
                            pageSize={PAGE_SIZE}
                            page={page}
                            onPageChange={(e) => setPage(e.page)}
                            alignSelf="center"
                        >
                            <ButtonGroup variant="ghost" size="sm">
                                <Pagination.PrevTrigger asChild>
                                    <IconButton aria-label="前のページ">
                                        <ChevronLeftIcon />
                                    </IconButton>
                                </Pagination.PrevTrigger>

                                <Pagination.Items
                                    render={(p) => (
                                        <IconButton
                                            aria-label={`${p.value} ページ目`}
                                            variant={{ base: "ghost", _selected: "outline" }}
                                        >
                                            {p.value}
                                        </IconButton>
                                    )}
                                />

                                <Pagination.NextTrigger asChild>
                                    <IconButton aria-label="次のページ">
                                        <ChevronRightIcon />
                                    </IconButton>
                                </Pagination.NextTrigger>
                            </ButtonGroup>
                        </Pagination.Root>
                    </Stack>
                )}
            </Stack>
        </MainLayout>
    )
}
