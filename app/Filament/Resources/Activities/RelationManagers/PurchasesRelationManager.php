<?php

namespace App\Filament\Resources\Activities\RelationManagers;

use App\Filament\Resources\ActivityPurchases\ActivityPurchaseResource;
use App\Filament\Support\Columns;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class PurchasesRelationManager extends RelationManager
{
    protected static string $relationship = 'purchases';

    protected static ?string $title = 'المشتريات';

    protected static ?string $modelLabel = 'مشترى';

    protected static ?string $pluralModelLabel = 'المشتريات';

    public function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components(ActivityPurchaseResource::formComponents(withActivity: false));
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('purchased_on', 'desc')
            ->columns(ActivityPurchaseResource::tableColumns(withActivity: false))
            ->headerActions([CreateAction::make()->modalWidth('3xl')])
            ->recordActions([
                Columns::previewAction('invoice_url', 'معاينة الفاتورة'),
                EditAction::make()->modalWidth('3xl'),
                DeleteAction::make(),
            ])
            ->emptyStateHeading('لا توجد مشتريات لهذا النشاط');
    }
}
