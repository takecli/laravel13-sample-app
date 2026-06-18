<?php

namespace App\Http\Requests\Team;

use App\Applications\Input\Team\CreateTeamInput;
use App\Domains\Enums\Team\PublicStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class CreateTeamRequest extends FormRequest
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
            'name' => ['required', 'string'],
            'description' => ['required', 'string'],
            'public_status' => ['required', new Enum(PublicStatus::class)],
        ];
    }

    /**
     * 検証済みの public_status を enum で返す。
     *
     * rules() の Enum バリデーション通過後に呼ぶこと。
     *
     * @return PublicStatus
     */
    public function publicStatus(): PublicStatus
    {
        return PublicStatus::from($this->validated('public_status'));
    }

    /**
     * 検証済みリクエストを Application 層の入力 DTO へ組み立てる。
     *
     * snake_case キー・文字列 enum・デフォルト補完といった HTTP 入力の
     * 詰め替えはこの境界に集約し、ListTeamInput は純粋な型付き DTO に保つ。
     *
     * @return CreateTeamInput
     */
    public function toInput(): CreateTeamInput
    {
        return new CreateTeamInput(
            name: $this->validated('name'),
            description: $this->validated('description'),
            publicStatus: $this->publicStatus()
        );
    }
}
