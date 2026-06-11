<?php

interface NoteRepositoryInterface
{
    /**
     * 記事作成
     *
     * @param  CreateNoteInput  $input
     */
    public function CreateNote(CreateNoteInput $input): Note;
}
