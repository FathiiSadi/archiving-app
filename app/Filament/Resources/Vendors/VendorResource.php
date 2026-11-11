<?php

namespace App\Filament\Resources\Vendors;

use App\Enums\Team;
use App\Enums\VendorType;
use App\Filament\Resources\Vendors\Pages\ManageVendors;
use App\Filament\Support\Columns;
use App\Filament\Support\Fields;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

class VendorResource extends Resource
{
    protected static ?string $model = \App\Models\Vendor::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static string|UnitEnum|null $navigationGroup = 'فريق الشؤون الداخلية';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'مورّد / مكان';

    protected static ?string $pluralModelLabel = 'المورّدون والأماكن';

    protected static ?string $recordTitleAttribute = 'name';

    public static function formComponents(): array
    {
        return [
            TextInput::make('name')->label('الاسم')->required()->maxLength(255),
            ToggleButtons::make('type')->label('النوع')->options(VendorType::class)->default(VendorType::Vendor)->inline()->grouped()->required(),
            TextInput::make('service')->label('الخدمة / التخصص')->placeholder('مثال: ضيافة، طباعة، قاعة مناسبات')->maxLength(255),
            TextInput::make('contact_person')->label('الشخص المسؤول')->maxLength(255),
            Fields::phone(),
            Fields::ratingOutOfTen('rating', 'التقييم'),
            Fields::link('invoice_url', 'رابط صورة الفاتورة / عرض السعر', 'رابط على Google Drive')->columnSpanFull(),
            Radio::make('suitable_for_future')
                ->label('صالح للتعامل في الأنشطة القادمة؟')
                ->boolean(trueLabel: 'نعم — القائمة البيضاء', falseLabel: 'لا — القائمة السوداء')
                ->inline()
                ->columnSpanFull(),
            Textarea::make('notes')->label('ملاحظات')->rows(2)->columnSpanFull(),
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components(self::formComponents());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('rating', 'desc')
            ->columns([
                TextColumn::make('name')->label('الاسم')->searchable()->weight('medium')
                    ->description(fn ($record) => $record->service),
                TextColumn::make('type')->label('النوع')->badge(),
                Columns::phone(Team::InternalAffairs),
                Columns::rating('rating', 'التقييم', 10),
                IconColumn::make('suitable_for_future')->label('صالح مستقبلاً')
                    ->boolean()->alignCenter()
                    ->trueIcon(Heroicon::OutlinedHandThumbUp)->falseIcon(Heroicon::OutlinedHandThumbDown),
                TextColumn::make('internal_activities_count')->label('مرات التعامل')->counts('internalActivities')->alignCenter(),
                Columns::link('invoice_url', 'الفاتورة'),
            ])
            ->filters([
                SelectFilter::make('type')->label('النوع')->options(VendorType::class),
                TernaryFilter::make('suitable_for_future')->label('القائمة')
                    ->trueLabel('القائمة البيضاء')->falseLabel('القائمة السوداء'),
            ])
            ->recordActions([ActionGroup::make([
                Columns::previewAction('invoice_url', 'معاينة الفاتورة'),
                EditAction::make()->modalWidth('3xl'),
                DeleteAction::make(),
            ])]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageVendors::route('/')];
    }
}
