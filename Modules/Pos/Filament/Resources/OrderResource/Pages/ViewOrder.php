<?php

declare(strict_types=1);

namespace Modules\Pos\Filament\Resources\OrderResource\Pages;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Resources\Pages\ViewRecord;
use Modules\Pos\Filament\Resources\OrderResource;

final class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Order')
                ->columns(3)
                ->schema([
                    TextEntry::make('invoice_number'),
                    TextEntry::make('status')->badge(),
                    TextEntry::make('grand_total')->money('KES'),
                ]),

            Section::make('Items')
                ->schema([
                    RepeatableEntry::make('items')
                        ->schema([
                            TextEntry::make('product_name_snapshot')->label('Product'),
                            TextEntry::make('quantity'),
                            TextEntry::make('unit_price')->money('KES'),
                            TextEntry::make('line_total')->money('KES'),
                        ])
                        ->columns(4),
                ]),

            Section::make('Payments')
                ->schema([
                    RepeatableEntry::make('payments')
                        ->schema([
                            TextEntry::make('method')->badge(),
                            TextEntry::make('status')->badge(),
                            TextEntry::make('amount')->money('KES'),
                            TextEntry::make('mpesa_receipt_number')->label('M-Pesa receipt'),
                        ])
                        ->columns(4),
                ]),
        ]);
    }
}
