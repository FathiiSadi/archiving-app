<?php

namespace App\Filament\Resources\ApprovedActivities;

use App\Enums\Team;
use App\Filament\Resources\ApprovedActivities\Pages\ManageApprovedActivities;
use App\Filament\Support\Fields;
use App\Filament\Support\Filters;
use App\Models\ApprovedActivity;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;
use UnitEnum;

class ApprovedActivityResource extends Resource
{
    protected static ?string $model = ApprovedActivity::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCheckCircle;

    protected static string|UnitEnum|null $navigationGroup = 'الإعدادات';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'نشاط معتمد';

    protected static ?string $pluralModelLabel = 'الأنشطة المعتمدة';

    public static function form(Schema $schema): Schema
    {
        $teamOptions = collect(Team::withApprovedActivities())->mapWithKeys(fn (Team $t) => [$t->value => $t->getLabel()])->all();

        return $schema->columns(2)->components([
            Fields::semester(),
            ToggleButtons::make('team')->label('الفريق')->options($teamOptions)->inline()->required()->live(),
            TextInput::make('name')->label('اسم النشاط')->required()->maxLength(255)
                ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule, Get $get) => $rule
                    ->where('semester_id', $get('semester_id'))
                    ->where('team', $get('team') instanceof Team ? $get('team')->value : $get('team')))
                ->validationMessages(['unique' => 'هذا النشاط معتمد مسبقاً لنفس الفريق في هذا الفصل.']),
            Fields::money('estimated_budget', 'الميزانية التقديرية'),
            Textarea::make('notes')->label('ملاحظات')->rows(2)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('semester'))
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')->label('النشاط')->searchable()->weight('medium'),
                TextColumn::make('team')->label('الفريق')->badge(),
                TextColumn::make('semester.label')->label('الفصل')->badge()->color('gray'),
                TextColumn::make('estimated_budget')->label('الميزانية التقديرية')->numeric(decimalPlaces: 2)->suffix(' د.أ')->placeholder('—'),
            ])
            ->filters([
                Filters::semester(),
                SelectFilter::make('team')->label('الفريق')->options(
                    collect(Team::withApprovedActivities())->mapWithKeys(fn (Team $t) => [$t->value => $t->getLabel()])->all()
                ),
            ])
            ->recordActions([ActionGroup::make([
                EditAction::make()->modalWidth('2xl'),
                ReplicateAction::make()->label('نسخ لفصل آخر')
                    ->schema([Fields::semester()])
                    ->modalHeading('نسخ النشاط المعتمد إلى فصل آخر'),
                DeleteAction::make(),
            ])]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageApprovedActivities::route('/')];
    }
}
