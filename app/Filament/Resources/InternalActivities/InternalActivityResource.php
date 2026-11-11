<?php

namespace App\Filament\Resources\InternalActivities;

use App\Enums\Team;
use App\Filament\Resources\InternalActivities\Pages\CreateInternalActivity;
use App\Filament\Resources\InternalActivities\Pages\EditInternalActivity;
use App\Filament\Resources\InternalActivities\Pages\ListInternalActivities;
use App\Filament\Resources\InternalActivities\Pages\ViewInternalActivity;
use App\Filament\Resources\Vendors\VendorResource;
use App\Filament\Support\Columns;
use App\Filament\Support\Fields;
use App\Filament\Support\Filters;
use App\Models\ApprovedActivity;
use App\Models\InternalActivity;
use App\Support\Kpi\Kpi;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class InternalActivityResource extends Resource
{
    protected static ?string $model = InternalActivity::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected static string|UnitEnum|null $navigationGroup = 'فريق الشؤون الداخلية';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'نشاط داخلي';

    protected static ?string $pluralModelLabel = 'الأنشطة الداخلية';

    public static function form(Schema $schema): Schema
    {
        $suggestRevenue = function (Get $get, Set $set): void {
            // Revenue is suggested as cost per person × attendance, and can still be edited.
            if (filled($get('cost_per_person')) && filled($get('participants'))) {
                $set('revenue', round((float) $get('cost_per_person') * (int) $get('participants'), 3));
            }
        };

        return $schema->columns(3)->components([
            Section::make('بيانات النشاط')
                ->icon(Heroicon::OutlinedHome)
                ->columns(2)
                ->columnSpan(2)
                ->schema([
                    Fields::semester()->afterStateUpdated(fn (Set $set) => $set('approved_activity_id', null)),
                    DatePicker::make('held_on')->label('التاريخ')->native(false)->displayFormat('d/m/Y')->default(now())->required(),
                    Select::make('approved_activity_id')
                        ->label('النشاط (من القائمة المعتمدة)')
                        ->options(fn (Get $get) => ApprovedActivity::optionsFor(Team::InternalAffairs, $get('semester_id')))
                        ->native(false)
                        ->searchable()
                        ->required()
                        ->helperText('تُدار القائمة المعتمدة من الإعدادات لكل فصل.')
                        ->columnSpanFull(),
                    Select::make('vendor_id')
                        ->label('المورّد / المكان')
                        ->relationship('vendor', 'name')
                        ->searchable()
                        ->preload()
                        ->createOptionForm(VendorResource::formComponents())
                        ->createOptionModalHeading('إضافة مورّد جديد'),
                    Fields::rating('final_rating', 'التقييم النهائي (KPI)'),
                ]),
            Section::make('المالية والحضور')
                ->icon(Heroicon::OutlinedBanknotes)
                ->columnSpan(1)
                ->schema([
                    Fields::money('cost_per_person', 'التكلفة على الفرد')->required()->live(onBlur: true)->afterStateUpdated($suggestRevenue),
                    TextInput::make('participants')->label('عدد الحضور')->numeric()->minValue(0)->required()->live(onBlur: true)->afterStateUpdated($suggestRevenue),
                    Fields::money('revenue', 'الإيرادات')->required()->live(onBlur: true)
                        ->helperText('تُقترح تلقائياً = التكلفة على الفرد × الحضور، ويمكن تعديلها.'),
                    Fields::money('actual_cost', 'التكلفة الفعلية')->required()->live(onBlur: true),
                    Text::make(function (Get $get): string {
                        $net = (float) $get('revenue') - (float) $get('actual_cost');

                        return ($net >= 0 ? 'الفائض: ' : 'العجز: ').Kpi::money(abs($net));
                    })->color(fn (Get $get) => ((float) $get('revenue') - (float) $get('actual_cost')) >= 0 ? 'success' : 'danger')->weight('bold'),
                ]),
            Section::make('الأرشفة والفواتير')
                ->icon(Heroicon::OutlinedArchiveBox)
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    Fields::archiveStatus()->columnSpanFull(),
                    Fields::link('invoice_url', 'رابط صورة الفاتورة', 'رابط صورة الفاتورة على Google Drive'),
                    Fields::link('drive_folder_url', 'مجلد التوثيق', 'رابط مجلد الصور والتقارير على Google Drive'),
                    Textarea::make('notes')->label('ملاحظات')->rows(2)->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        $sum = fn () => Sum::make()->numeric(decimalPlaces: 2)->suffix(' د.أ');

        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['semester', 'approvedActivity', 'vendor']))
            ->defaultSort('held_on', 'desc')
            ->columns([
                TextColumn::make('approvedActivity.name')->label('النشاط')->searchable()->weight('medium')
                    ->description(fn (InternalActivity $r) => $r->vendor?->name),
                TextColumn::make('held_on')->label('التاريخ')->date('d/m/Y')->sortable(),
                Columns::semester(),
                TextColumn::make('participants')->label('الحضور')->alignCenter()->summarize(Sum::make()),
                Columns::money('cost_per_person', 'التكلفة/فرد')->toggleable(),
                Columns::money('revenue', 'الإيرادات')->summarize($sum()),
                Columns::money('actual_cost', 'التكلفة الفعلية')->summarize($sum()),
                Columns::money('net_result', 'الفائض / العجز')
                    ->color(fn ($state) => (float) $state >= 0 ? 'success' : 'danger')
                    ->weight('medium')
                    ->summarize($sum()),
                Columns::rating('final_rating', 'التقييم'),
                Columns::archiveStatus(),
                Columns::link('invoice_url', 'الفاتورة'),
            ])
            ->filters([
                Filters::semester(),
                Filters::academicYear(),
                SelectFilter::make('vendor_id')->label('المورّد')->relationship('vendor', 'name'),
                Filters::archiveStatus(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    Columns::previewAction('invoice_url', 'معاينة الفاتورة'),
                    Columns::previewAction('drive_folder_url', 'معاينة مجلد التوثيق'),
                    DeleteAction::make(),
                ]),
            ])
            ->emptyStateHeading('لا توجد أنشطة داخلية في هذا الفصل');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInternalActivities::route('/'),
            'create' => CreateInternalActivity::route('/create'),
            'view' => ViewInternalActivity::route('/{record}'),
            'edit' => EditInternalActivity::route('/{record}/edit'),
        ];
    }
}
