<?php

namespace App\Models;

use App\Enums\Team;
use App\Models\Contracts\TeamOwned;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CircleEvaluation extends Model implements TeamOwned
{
    /** BR-07: the five criteria, each rated 1–5. */
    public const CRITERIA = [
        'memorization_accuracy' => 'دقة الحفظ',
        'interaction' => 'التفاعل',
        'punctuality' => 'الالتزام بالوقت',
        'tajweed_rules' => 'أحكام التجويد',
        'plan_achievement' => 'تحقيق خطة التسميع',
    ];

    protected $fillable = [
        'quran_circle_id', 'evaluated_on', 'memorization_accuracy', 'interaction', 'punctuality',
        'tajweed_rules', 'plan_achievement', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'evaluated_on' => 'date',
            'memorization_accuracy' => 'integer',
            'interaction' => 'integer',
            'punctuality' => 'integer',
            'tajweed_rules' => 'integer',
            'plan_achievement' => 'integer',
            'average' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (CircleEvaluation $evaluation): void {
            $scores = array_map(fn (string $c) => (int) $evaluation->{$c}, array_keys(self::CRITERIA));
            $evaluation->average = round(array_sum($scores) / count($scores), 2);
        });
    }

    public static function owningTeam(): Team
    {
        return Team::Quran;
    }

    public function circle(): BelongsTo
    {
        return $this->belongsTo(QuranCircle::class, 'quran_circle_id');
    }
}
