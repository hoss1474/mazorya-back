<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServicesResource\Pages;
use App\Models\Service;

use BackedEnum;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

use Filament\Resources\Resource;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;

use Filament\Tables\Table;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;

class ServicesResource extends Resource
{
    /*
    |--------------------------------------------------------------------------
    | Resource Configuration
    |--------------------------------------------------------------------------
    */

    protected static ?string $model = Service::class;

    protected static string|BackedEnum|null $navigationIcon =
        'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'سرویس‌ها';

    protected static ?string $modelLabel = 'سرویس';

    protected static ?string $pluralModelLabel = 'سرویس‌ها';

    /*
    |--------------------------------------------------------------------------
    | Form
    |--------------------------------------------------------------------------
    */

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([

                /*
                |--------------------------------------------------------------------------
                | Main Information
                |--------------------------------------------------------------------------
                */

                Section::make('اطلاعات اصلی')
                    ->description(
                        'مدیریت تصاویر و وضعیت نمایش سرویس'
                    )
                    ->schema([

                        // Main Image

                        static::serviceImageUpload(
                            'image',
                            'تصویر اصلی سرویس'
                        )
                            ->required(),

                        // Secondary Image

                        static::serviceImageUpload(
                            'image2',
                            'تصویر دوم سرویس'
                        ),

                        // Active Status

                        Toggle::make('is_active')
                            ->label('فعال / غیرفعال')
                            ->default(true)
                            ->columnSpanFull(),

                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                /*
                |--------------------------------------------------------------------------
                | Service Translations
                |--------------------------------------------------------------------------
                */

                Section::make('ترجمه‌ها')
                    ->description(
                        'برای هر زبان اطلاعات سرویس را جداگانه ثبت کنید.'
                    )
                    ->schema([

                        Repeater::make('translations')
                            ->label('ترجمه‌های سرویس')
                            ->relationship('translations')

                            ->schema([

                                // Language

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
                                    ->native(false)
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                    ->columnSpanFull(),

                                // Service Name

                                TextInput::make('name')
                                    ->label('عنوان سرویس')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->columnSpanFull(),

                                // Main Description

                                RichEditor::make('description')
                                    ->label('توضیحات سرویس')
                                    ->columnSpanFull(),

                                /*
                                |--------------------------------------------------------------------------
                                | Content Blocks 1 - 5
                                |--------------------------------------------------------------------------
                                */

                                ...static::contentBlocks(),

                            ])

                            ->columns(2)
                            ->minItems(1)
                            ->collapsible()
                            ->addActionLabel('افزودن ترجمه')

                            ->itemLabel(
                                fn (array $state): string =>
                                    strtoupper(
                                        $state['locale'] ?? '--'
                                    )
                                    . ' | '
                                    . (
                                    filled($state['name'] ?? null)
                                        ? $state['name']
                                        : 'سرویس بدون عنوان'
                                    )
                            ),

                    ])
                    ->columnSpanFull(),

            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Image Upload Configuration
    |--------------------------------------------------------------------------
    */

    private static function serviceImageUpload(
        string $field,
        string $label
    ): FileUpload {
        return FileUpload::make($field)
            ->label($label)
            ->image()
            ->disk('api_public')
            ->directory('service')
            ->visibility('public')
            ->acceptedFileTypes([
                'image/jpeg',
                'image/png',
                'image/webp',
            ])
            ->imagePreviewHeight('200')
            ->panelLayout('integrated');
    }

    /*
    |--------------------------------------------------------------------------
    | Content Blocks
    |--------------------------------------------------------------------------
    |
    | Generate title_1 through title_5
    | Generate description_1 through description_5
    |
    */

    private static function contentBlocks(): array
    {
        $components = [];

        for ($index = 1; $index <= 5; $index++) {

            $components[] = TextInput::make(
                "title_{$index}"
            )
                ->label("عنوان {$index}")
                ->maxLength(255);

            $components[] = RichEditor::make(
                "description_{$index}"
            )
                ->label("توضیح {$index}");

        }

        return $components;
    }

    /*
    |--------------------------------------------------------------------------
    | Table
    |--------------------------------------------------------------------------
    */

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                // Service ID

                TextColumn::make('id')
                    ->label('شماره سرویس')
                    ->searchable()
                    ->sortable(),

                // Persian Service Name

                TextColumn::make('faTranslation.name')
                    ->label('نام سرویس')
                    ->searchable()
                    ->sortable()
                    ->placeholder('ترجمه فارسی ندارد'),

                // Active Status

                IconColumn::make('is_active')
                    ->label('وضعیت')
                    ->boolean()
                    ->sortable(),

            ])

            ->defaultSort('id', 'desc')

            /*
            |--------------------------------------------------------------------------
            | Record Actions
            |--------------------------------------------------------------------------
            */

            ->recordActions([

                EditAction::make()
                    ->label('ویرایش')
                    ->icon('heroicon-o-pencil-square'),

                DeleteAction::make()
                    ->label('حذف')
                    ->icon('heroicon-o-trash')
                    ->requiresConfirmation(),

            ])

            /*
            |--------------------------------------------------------------------------
            | Bulk Actions
            |--------------------------------------------------------------------------
            */

            ->toolbarActions([

                BulkActionGroup::make([

                    DeleteBulkAction::make()
                        ->label('حذف سرویس‌های انتخاب‌شده')
                        ->requiresConfirmation(),

                ]),

            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Resource Pages
    |--------------------------------------------------------------------------
    */

    public static function getPages(): array
    {
        return [

            'index' => Pages\ListServices::route('/'),

            'create' => Pages\CreateServices::route('/create'),

            'edit' => Pages\EditServices::route('/{record}/edit'),

        ];
    }
}
