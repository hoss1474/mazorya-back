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
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ServicesResource extends Resource
{
    protected static ?string $model = Service::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'سرویس‌ها';

    protected static ?string $modelLabel = 'سرویس';

    protected static ?string $pluralModelLabel = 'سرویس‌ها';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('تصاویر و وضعیت سرویس')
                ->schema([
                    static::serviceImageUpload('image', 'تصویر اصلی (لپ‌تاپ)')->required(),
                    static::serviceImageUpload('image2', 'تصویر دوم (Our Approach)'),
                    Toggle::make('is_active')
                        ->label('فعال / غیرفعال')
                        ->default(true)
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->columnSpanFull(),

            Section::make('محتوای چندزبانه')
                ->description('برای هر زبان عنوان، اسلاگ و محتوای اختصاصی سرویس را ثبت کنید.')
                ->schema([
                    Repeater::make('translations')
                        ->label('ترجمه‌های سرویس')
                        ->relationship('translations')
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
                                ->native(false)
                                ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                ->columnSpanFull(),

                            TextInput::make('name')
                                ->label('عنوان سرویس (Hero)')
                                ->required()
                                ->maxLength(255)
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (?string $state, Get $get, Set $set): void {
                                    if (filled($get('slug'))) {
                                        return;
                                    }

                                    $slug = static::slugify($state ?? '');
                                    if ($slug !== '') {
                                        $set('slug', $slug);
                                    }
                                }),

                            TextInput::make('slug')
                                ->label('Slug')
                                ->required()
                                ->maxLength(255)
                                ->helperText('آدرس اختصاصی این سرویس در زبان انتخاب‌شده.'),

                            Textarea::make('short_description')
                                ->label('توضیح کوتاه Hero')
                                ->rows(3)
                                ->columnSpanFull(),

                            Textarea::make('description')
                                ->label('About the Service')
                                ->rows(5)
                                ->columnSpanFull(),

                            Repeater::make('deliverables')
                                ->label('What We Deliver')
                                ->schema([
                                    TextInput::make('title')
                                        ->label('عنوان کارت')
                                        ->required()
                                        ->maxLength(255),
                                    Textarea::make('description')
                                        ->label('توضیح کارت')
                                        ->rows(3),
                                ])
                                ->columns(2)
                                ->defaultItems(0)
                                ->collapsible()
                                ->addActionLabel('افزودن کارت')
                                ->columnSpanFull(),

                            Repeater::make('process')
                                ->label('مراحل اجرای سرویس')
                                ->schema([
                                    TextInput::make('title')
                                        ->label('عنوان مرحله')
                                        ->required()
                                        ->maxLength(255),
                                    Textarea::make('description')
                                        ->label('شرح مرحله')
                                        ->rows(3),
                                ])
                                ->columns(2)
                                ->defaultItems(0)
                                ->collapsible()
                                ->addActionLabel('افزودن مرحله')
                                ->columnSpanFull(),

                            Textarea::make('approach_description')
                                ->label('Our Approach')
                                ->rows(5)
                                ->columnSpanFull(),

                            Repeater::make('sections')
                                ->label('توضیحات تکمیلی')
                                ->schema([
                                    TextInput::make('title')
                                        ->label('عنوان بخش')
                                        ->required()
                                        ->maxLength(255),
                                    Textarea::make('description')
                                        ->label('توضیحات بخش')
                                        ->rows(4),
                                ])
                                ->columns(2)
                                ->defaultItems(0)
                                ->collapsible()
                                ->addActionLabel('افزودن بخش')
                                ->columnSpanFull(),
                        ])
                        ->columns(2)
                        ->minItems(1)
                        ->collapsible()
                        ->addActionLabel('افزودن ترجمه')
                        ->itemLabel(fn (array $state): string =>
                            strtoupper($state['locale'] ?? '--') . ' | ' . ($state['name'] ?? 'بدون عنوان'))
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),
        ]);
    }

    private static function slugify(string $value): string
    {
        $value = Str::lower(trim($value));
        $slug = preg_replace('/[^\p{L}\p{M}\p{N}]+/u', '-', $value);

        return Str::substr(trim($slug ?? '', '-'), 0, 200);
    }

    private static function serviceImageUpload(string $field, string $label): FileUpload
    {
        return FileUpload::make($field)
            ->label($label)
            ->image()
            ->disk('api_public')
            ->directory('service')
            ->visibility('public')
            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
            ->imagePreviewHeight('200')
            ->panelLayout('integrated');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('شناسه')->sortable(),
                TextColumn::make('faTranslation.name')
                    ->label('عنوان سرویس')
                    ->searchable()
                    ->sortable()
                    ->placeholder('ترجمه فارسی ندارد'),
                IconColumn::make('is_active')->label('وضعیت')->boolean()->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->recordActions([
                EditAction::make()->label('ویرایش'),
                DeleteAction::make()->label('حذف')->requiresConfirmation(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('حذف سرویس‌های انتخاب‌شده')
                        ->requiresConfirmation(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServices::route('/'),
            'create' => Pages\CreateServices::route('/create'),
            'edit' => Pages\EditServices::route('/{record}/edit'),
        ];
    }
}
