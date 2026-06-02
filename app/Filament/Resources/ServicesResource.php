<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServicesResource\Pages;
use App\Models\Service;
use Filament\Forms;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms\Components\RichEditor;

class ServicesResource extends Resource
{
    protected static ?string $model = Service::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $modelLabel = 'سرویس ها ';

    public static function form(Form $form): Form
    {
        return $form->schema([

            /* ================= اطلاعات اصلی ================= */
            Section::make('اطلاعات اصلی')
                ->schema([
                    FileUpload::make('image')
                        ->label('تصویر اصلی سرویس')
                        ->image()
                        ->disk('api_public')
                        ->directory('service')
                        ->required(),

                    FileUpload::make('image2')
                        ->label('تصویر دوم سرویس')
                        ->image()
                        ->disk('api_public')
                        ->directory('service'),

                    Forms\Components\Toggle::make('is_active')
                        ->label('فعال / غیرفعال')
                        ->default(true),
                ])
                ->columns(2),

            /* ================= ترجمه‌ها ================= */
            Section::make('ترجمه‌ها')
                ->description('برای هر زبان یک آیتم اضافه کن')
                ->schema([
                    Repeater::make('translations')
                        ->relationship()
                        ->schema([
                            Select::make('locale')
                                ->label('زبان')
                                ->options([
                                    'fa' => 'فارسی',
                                    'en' => 'English',
                                    'de' => 'Deutsch',
                                    'fr' => 'Français',
                                    'es' => 'Español',
                                    'ar' => 'العربية',
                                ])
                                ->required()
                                ->columnSpanFull(),

                            Forms\Components\TextInput::make('name')
                                ->label('عنوان سرویس')
                                ->required(),

                            Forms\Components\RichEditor::make('description')
                                ->label('توضیحات سرویس'),

                            // بلاک‌های محتوا
                            Forms\Components\TextInput::make('title_1')->label('عنوان 1'),
                            Forms\Components\RichEditor::make('description_1')->label('توضیح 1'),

                            Forms\Components\TextInput::make('title_2')->label('عنوان 2'),
                            Forms\Components\RichEditor::make('description_2')->label('توضیح 2'),

                            Forms\Components\TextInput::make('title_3')->label('عنوان 3'),
                            Forms\Components\RichEditor::make('description_3')->label('توضیح 3'),

                            Forms\Components\TextInput::make('title_4')->label('عنوان 4'),
                            Forms\Components\RichEditor::make('description_4')->label('توضیح 4'),

                            Forms\Components\TextInput::make('title_5')->label('عنوان 5'),
                            Forms\Components\RichEditor::make('description_5')->label('توضیح 5'),
                        ])
                        ->columns(2)
                        ->minItems(1)
                        ->collapsible()
                ])
                ->columnSpanFull(),
        ]);
    }

    private static function contentBlock(string $index, bool $required = true): Forms\Components\Group
    {
        return Forms\Components\Group::make([
            Forms\Components\TextInput::make("title_{$index}")
                ->label("عنوان {$index}")
                ->required($required),

            Forms\Components\TextInput::make("title_{$index}_en")
                ->label("عنوان {$index} (انگلیسی)")
                ->required($required),

            Forms\Components\Textarea::make("description_{$index}")
                ->label("توضیحات {$index}")
                ->required($required),

            Forms\Components\Textarea::make("description_{$index}_en")
                ->label("توضیحات {$index} (انگلیسی)")
                ->required($required),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('شماره سرویس')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('faTranslation.name')
                    ->label('نام سرویس')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BooleanColumn::make('is_active')
                    ->label('وضعیت'),


            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListServices::route('/'),
            'create' => Pages\CreateServices::route('/create'),
            'edit'   => Pages\EditServices::route('/{record}/edit'),
        ];
    }
}
