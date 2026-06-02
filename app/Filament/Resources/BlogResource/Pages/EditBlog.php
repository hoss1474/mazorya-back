<?php

namespace App\Filament\Resources\BlogResource\Pages;

use App\Filament\Resources\BlogResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;

class EditBlog extends EditRecord
{
    protected static string $resource = BlogResource::class;

    protected function beforeSave(): void
    {
        logger('beforeSave fired');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        foreach (['image_1', 'image_2', 'image_3'] as $field) {
            if (array_key_exists($field, $data) && $data[$field] === null) {
                if ($this->record->{$field}) {
                    Storage::disk('public')->delete($this->record->{$field});
                }
            }
        }

        return $data;
    }
}
