<?php

namespace App\Filament\Resources\Users;

use App\Enums\UserRole;
use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\User;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Password;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserCircle;

    protected static string|UnitEnum|null $navigationGroup = 'الإعدادات';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'مستخدم';

    protected static ?string $pluralModelLabel = 'المستخدمون والصلاحيات';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('name')->label('الاسم')->required()->maxLength(255),
            TextInput::make('email')->label('البريد الإلكتروني')->email()->required()->unique(ignoreRecord: true)
                ->extraInputAttributes(['dir' => 'ltr']),
            Select::make('role')->label('الدور')->options(UserRole::class)->required()->native(false)
                // An admin cannot change their own role, so the system always keeps at least one admin.
                ->disabled(fn (?User $record) => (bool) $record?->is(auth()->user()))
                ->helperText('قائد الفريق وحده يستطيع إدخال بيانات فريقه.'),
            TextInput::make('password')->label('كلمة المرور')->password()->revealable()
                ->required(fn (string $operation) => $operation === 'create')
                ->dehydrated(fn (?string $state) => filled($state))
                ->rule(Password::default())
                ->helperText(fn (string $operation) => $operation === 'edit' ? 'اتركها فارغة للإبقاء على كلمة المرور الحالية.' : null),
            Toggle::make('is_active')->label('الحساب مفعّل')->default(true)
                ->disabled(fn (?User $record) => $record?->is(auth()->user())),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')->label('الاسم')->searchable()->weight('medium'),
                TextColumn::make('email')->label('البريد')->searchable()->extraAttributes(['dir' => 'ltr']),
                TextColumn::make('role')->label('الدور')->badge(),
                IconColumn::make('is_active')->label('مفعّل')->boolean()->alignCenter(),
                TextColumn::make('created_at')->label('أُنشئ')->since()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([SelectFilter::make('role')->label('الدور')->options(UserRole::class)])
            ->recordActions([ActionGroup::make([
                EditAction::make()->modalWidth('2xl'),
                DeleteAction::make()->hidden(fn (User $record) => $record->is(auth()->user())),
            ])]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageUsers::route('/')];
    }
}
