<?php

namespace App\Filament\Resources\Semesters;

use App\Enums\Team;
use App\Enums\Term;
use App\Filament\Resources\Semesters\Pages\ManageSemesters;
use App\Filament\Support\Fields;
use App\Models\Semester;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;
use UnitEnum;

class SemesterResource extends Resource
{
    protected static ?string $model = Semester::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendar;

    protected static string|UnitEnum|null $navigationGroup = 'الإعدادات';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'فصل دراسي';

    protected static ?string $pluralModelLabel = 'الفصول والميزانيات';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Grid::make(2)->schema([
                TextInput::make('academic_year')
                    ->label('العام الجامعي')
                    ->placeholder('2025/2026')
                    ->regex('~^\d{4}/\d{4}$~')
                    ->validationMessages(['regex' => 'الصيغة المطلوبة: 2025/2026', 'unique' => 'هذا الفصل موجود مسبقاً.'])
                    ->extraInputAttributes(['dir' => 'ltr'])
                    ->required()
                    ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule, Get $get) => $rule->where('term', $get('term') instanceof Term ? $get('term')->value : $get('term'))),
                ToggleButtons::make('term')->label('الفصل')->options(Term::class)->inline()->grouped()->required(),
                DatePicker::make('starts_on')->label('بداية الفصل')->native(false)->displayFormat('d/m/Y'),
                DatePicker::make('ends_on')->label('نهاية الفصل')->native(false)->displayFormat('d/m/Y')->afterOrEqual('starts_on'),
                TextInput::make('tajweed_target_rate')
                    ->label('هدف إنجاز مستويات التجويد')
                    ->numeric()->minValue(70)->maxValue(100)->default(70)->suffix('٪')->required()
                    ->helperText('الحد الأدنى 70٪ حسب متطلبات المبادرة.'),
                Toggle::make('is_current')->label('الفصل الحالي')->helperText('يُستخدم افتراضياً في النماذج ولوحات المتابعة.')->inline(false),
            ]),
            Section::make('الميزانية المعتمدة والسقف التقديري')
                ->description('لكل فريق في هذا الفصل — المبالغ بالدينار الأردني.')
                ->icon(Heroicon::OutlinedBanknotes)
                ->compact()
                ->schema([
                    Repeater::make('budgets')
                        ->hiddenLabel()
                        ->relationship()
                        ->schema([
                            Select::make('team')->label('الفريق')->options(Team::class)->required()
                                ->disableOptionsWhenSelectedInSiblingRepeaterItems(),
                            Fields::money('approved_budget', 'الميزانية المعتمدة')->default(0)->required(),
                            Fields::money('budget_cap', 'السقف التقديري')->default(0)->required(),
                        ])
                        ->columns(3)
                        ->default([['team' => Team::Activities->value, 'approved_budget' => 0, 'budget_cap' => 0]])
                        ->addActionLabel('إضافة ميزانية فريق')
                        ->maxItems(count(Team::cases())),
                ]),
            Textarea::make('notes')->label('ملاحظات')->rows(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->ordered()->withCount(['approvedActivities'])->withSum('budgets', 'budget_cap'))
            ->columns([
                TextColumn::make('academic_year')->label('العام الجامعي')->weight('medium'),
                TextColumn::make('term')->label('الفصل')->badge(),
                TextColumn::make('starts_on')->label('من')->date('d/m/Y')->placeholder('—'),
                TextColumn::make('ends_on')->label('إلى')->date('d/m/Y')->placeholder('—'),
                TextColumn::make('budgets_sum_budget_cap')->label('مجموع السقوف')->numeric(decimalPlaces: 2)->suffix(' د.أ')->placeholder('—'),
                TextColumn::make('tajweed_target_rate')->label('هدف التجويد')->suffix('٪')->alignCenter(),
                TextColumn::make('approved_activities_count')->label('أنشطة معتمدة')->alignCenter(),
                IconColumn::make('is_current')->label('الحالي')->boolean()->alignCenter(),
            ])
            ->recordActions([ActionGroup::make([EditAction::make()->modalWidth('4xl'), DeleteAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageSemesters::route('/')];
    }
}
