<?php

namespace App\Http\Requests\Team;

use App\Applications\Input\Team\ListTeamInput;
use App\Constants\Pagination;
use App\Domains\Enums\Team\PublicStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class ListTeamRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string'],
            'public_status' => ['nullable', new Enum(PublicStatus::class)],
            'page' => ['nullable', 'integer'],
            'limit' => ['nullable', 'integer'],
            'sort' => ['nullable', 'string'],
        ];
    }

    /**
     * 検証済みの public_status を enum で返す（未指定なら null）。
     *
     * rules() の Enum バリデーション通過後に呼ぶこと。
     *
     * @return ?PublicStatus
     */
    public function publicStatus(): ?PublicStatus
    {
        $value = $this->validated('public_status');

        return $value !== null ? PublicStatus::from($value) : null;
    }

    /**
     * 検証済みリクエストを Application 層の入力 DTO へ組み立てる。
     *
     * snake_case キー・文字列 enum・デフォルト補完といった HTTP 入力の
     * 詰め替えはこの境界に集約し、ListTeamInput は純粋な型付き DTO に保つ。
     *
     * @return ListTeamInput
     */
    public function toInput(): ListTeamInput
    {
        return new ListTeamInput(
            name: $this->validated('name'),
            publicStatus: $this->publicStatus(),
            page: (int) ($this->validated('page') ?? Pagination::PAGE_DEFAULT),
            limit: (int) ($this->validated('limit') ?? Pagination::LIMIT_DEFAULT),
            sort: $this->validated('sort') ?? Pagination::SORT_DEFAULT,
        );
    }
}
