<?php

namespace App\Filament\Support;

use App\Enums\Team;
use App\Support\DriveLink;
use App\Support\PhoneMask;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;

/** Reusable, consistent table columns and actions. */
final class Columns
{
    public static function semester(): TextColumn
    {
        return TextColumn::make('semester.label')
            ->label('الفصل')
            ->badge()
            ->color('gray')
            ->toggleable();
    }

    /** Small icon that opens the stored link in a new tab. */
    public static function link(string $name, string $label): IconColumn
    {
        return IconColumn::make($name)
            ->label($label)
            ->icon(fn (?string $state) => filled($state) ? Heroicon::OutlinedLink : Heroicon::OutlinedMinus)
            ->color(fn (?string $state) => filled($state) ? 'primary' : 'gray')
            ->url(fn (Model $record) => $record->{$name} ?: null, shouldOpenInNewTab: true)
            ->tooltip(fn (?string $state) => filled($state) ? 'فتح الرابط' : 'لا يوجد رابط')
            ->alignCenter()
            ->default('');
    }

    /** Phone numbers are masked unless the viewer leads the owning team or is an admin (BR-16). */
    public static function phone(Team $team, string $name = 'phone'): TextColumn
    {
        return TextColumn::make($name)
            ->label('الهاتف')
            ->formatStateUsing(fn (?string $state) => auth()->user()?->canSeeContactsOf($team) ? $state : PhoneMask::mask($state))
            ->copyable(fn () => (bool) auth()->user()?->canSeeContactsOf($team))
            ->extraAttributes(['dir' => 'ltr', 'class' => 'tabular-nums'])
            ->placeholder('—')
            ->toggleable();
    }

    public static function rating(string $name, string $label, int $outOf = 5): TextColumn
    {
        return TextColumn::make($name)
            ->label($label)
            ->formatStateUsing(fn ($state) => $state === null ? null : \App\Support\Kpi\Kpi::ratio(rtrim(rtrim(number_format((float) $state, 1), '0'), '.'), $outOf))
            ->badge()
            ->color(function ($state) use ($outOf) {
                if ($state === null) {
                    return 'gray';
                }
                $ratio = (float) $state / $outOf;

                return $ratio >= 0.8 ? 'success' : ($ratio >= 0.6 ? 'info' : ($ratio >= 0.4 ? 'warning' : 'danger'));
            })
            ->placeholder('—')
            ->sortable();
    }

    public static function money(string $name, string $label): TextColumn
    {
        return TextColumn::make($name)
            ->label($label)
            ->numeric(decimalPlaces: 2)
            ->suffix(' د.أ')
            ->extraAttributes(['class' => 'tabular-nums'])
            ->sortable();
    }

    public static function archiveStatus(): TextColumn
    {
        return TextColumn::make('archive_status')
            ->label('الأرشفة')
            ->badge()
            ->sortable();
    }

    /** Opens a Drive file or folder inside the panel (no upload, no download). */
    public static function previewAction(string $field, string $label = 'معاينة'): Action
    {
        return Action::make('preview'.str($field)->studly())
            ->label($label)
            ->icon(Heroicon::OutlinedEye)
            ->color('gray')
            ->visible(fn (Model $record) => DriveLink::canPreview($record->{$field}))
            ->modalHeading($label)
            ->modalWidth('5xl')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('إغلاق')
            ->modalContent(fn (Model $record) => view('filament.drive-preview', [
                'embed' => DriveLink::embedUrl($record->{$field}),
                'url' => $record->{$field},
            ]));
    }
}
