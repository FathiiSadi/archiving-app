<?php

namespace App\Filament\Support;

use App\Enums\ArchiveStatus;
use App\Enums\Gender;
use App\Models\Semester;
use App\Support\DriveLink;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Support\Icons\Heroicon;

/** Reusable, consistent form fields for every team. */
final class Fields
{
    public static function semester(string $name = 'semester_id'): Select
    {
        return Select::make($name)
            ->label('الفصل الدراسي')
            ->options(fn () => Semester::options())
            ->default(fn () => Semester::current()?->id)
            ->required()
            ->native(false)
            ->searchable()
            ->live();
    }

    /**
     * A link to a file or folder in Google Drive (or any other https link).
     * Nothing is ever uploaded to the system itself.
     */
    public static function link(string $name, string $label, string $hint = 'رابط ملف أو مجلد على Google Drive'): TextInput
    {
        return TextInput::make($name)
            ->label($label)
            ->url()
            ->maxLength(2048)
            ->rule('starts_with:https://')
            ->validationMessages(['starts_with' => 'يجب أن يبدأ الرابط بـ https://'])
            ->placeholder('https://drive.google.com/…')
            ->helperText($hint.' — تأكد من صلاحية المشاركة لفريق المبادرة.')
            ->prefixIcon(Heroicon::OutlinedLink)
            ->suffixAction(
                Action::make('open'.str($name)->studly())
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->tooltip('فتح الرابط')
                    ->url(fn (?string $state) => filled($state) ? $state : null, shouldOpenInNewTab: true)
                    ->visible(fn (?string $state) => filled($state))
            )
            ->dehydrateStateUsing(fn (?string $state) => filled($state) ? trim($state) : null);
    }

    public static function phone(string $name = 'phone'): TextInput
    {
        return TextInput::make($name)
            ->label('رقم الهاتف')
            ->tel()
            ->maxLength(20)
            ->regex('/^\+?[0-9\s\-]{7,20}$/')
            ->validationMessages(['regex' => 'رقم الهاتف غير صحيح.'])
            ->placeholder('07XXXXXXXX')
            ->extraInputAttributes(['dir' => 'ltr'])
            ->prefixIcon(Heroicon::OutlinedPhone)
            ->dehydrateStateUsing(fn (?string $state) => filled($state) ? preg_replace('/[\s\-]/', '', $state) : null);
    }

    public static function gender(string $name = 'gender'): ToggleButtons
    {
        return ToggleButtons::make($name)
            ->label('الجنس')
            ->options(Gender::class)
            ->inline()
            ->grouped();
    }

    /** 1–5 rating as a row of buttons — one click instead of a dropdown. */
    public static function rating(string $name, string $label, bool $required = false): ToggleButtons
    {
        return ToggleButtons::make($name)
            ->label($label)
            ->options([1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5'])
            ->colors([1 => 'danger', 2 => 'warning', 3 => 'gray', 4 => 'info', 5 => 'success'])
            ->inline()
            ->grouped()
            ->required($required);
    }

    /** Rating out of 10 (teachers, vendors). */
    public static function ratingOutOfTen(string $name, string $label): TextInput
    {
        return TextInput::make($name)
            ->label($label)
            ->numeric()
            ->minValue(0)
            ->maxValue(10)
            ->step(0.5)
            ->suffix('/ 10');
    }

    public static function money(string $name, string $label): TextInput
    {
        return TextInput::make($name)
            ->label($label)
            ->numeric()
            ->minValue(0)
            ->step(0.001)
            ->suffix('د.أ')
            ->extraInputAttributes(['dir' => 'ltr']);
    }

    public static function score(string $name, string $label): TextInput
    {
        return TextInput::make($name)
            ->label($label)
            ->numeric()
            ->minValue(0)
            ->maxValue(100)
            ->step(0.25)
            ->suffix('/ 100');
    }

    public static function archiveStatus(): ToggleButtons
    {
        return ToggleButtons::make('archive_status')
            ->label('حالة الأرشفة')
            ->options(ArchiveStatus::class)
            ->icons([
                ArchiveStatus::NotArchived->value => Heroicon::OutlinedXCircle,
                ArchiveStatus::Incomplete->value => Heroicon::OutlinedExclamationTriangle,
                ArchiveStatus::Complete->value => Heroicon::OutlinedCheckBadge,
            ])
            ->default(ArchiveStatus::NotArchived)
            ->inline()
            ->required();
    }

    public static function canPreview(?string $url): bool
    {
        return DriveLink::canPreview($url);
    }
}
