<?php

namespace App\Filament\Resources\TransitionResource\Pages;

use App\Filament\Resources\TransitionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTransitions extends ListRecords
{
    protected static string $resource = TransitionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
