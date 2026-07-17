<?php

namespace App\Filament\Resources\TransitionResource\Pages;

use App\Filament\Resources\TransitionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTransition extends EditRecord
{
    protected static string $resource = TransitionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
