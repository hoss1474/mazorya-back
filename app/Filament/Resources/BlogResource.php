<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BlogResource\Pages;
use App\Models\Blog;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BlogResource extends Resource
{
    protected static ?string $model = Blog::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Blogs';

    protected static ?string $modelLabel = 'مقاله';

    protected static ?string $pluralModelLabel = 'مقالات';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('تصاویر و اطلاعات انتشار')
                ->schema([
                    FileUpload::make('image_1')
                        ->label('تصویر شاخص بزرگ')
                        ->image()
                        ->disk('api_public')
                        ->directory('blogs')
                        ->visibility('public')
                        ->imagePreviewHeight('200')
                        ->maxSize(5120)
                        ->required(),

                    FileUpload::make('image_2')
                        ->label('تصویر داخل مقاله')
                        ->image()
                        ->disk('api_public')
                        ->directory('blogs')
                        ->visibility('public')
                        ->imagePreviewHeight('200')
                        ->maxSize(5120),

                    DateTimePicker::make('published_at')
                        ->label('تاریخ انتشار')
                        ->seconds(false),

                    Toggle::make('is_active')
                        ->label('فعال / غیرفعال')
                        ->default(true),
                ])
                ->columns(2)
                ->columnSpanFull(),

            Section::make('محتوای چندزبانه مقاله')
                ->description('عنوان، لینک و همه متن‌ها برای هر زبان مستقل هستند.')
                ->schema([
                    Repeater::make('translations')
                        ->label('ترجمه‌های مقاله')
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

                            TextInput::make('title')
                                ->label('عنوان مقاله (Hero)')
                                ->required()
                                ->maxLength(255)
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (?string $state, Get $get, Set $set): void {
                                    if (blank($get('slug'))) {
                                        $set('slug', static::slugify($state ?? ''));
                                    }
                                }),

                            TextInput::make('slug')
                                ->label('Slug')
                                ->maxLength(255)
                                ->required()
                                ->helperText('پس از ایجاد خودکار می‌توانید دستی تغییرش دهید.'),

                            TextInput::make('category_name')
                                ->label('عنوان دسته‌بندی')
                                ->maxLength(255),

                            Textarea::make('excerpt')
                                ->label('خلاصه زیر عنوان Hero')
                                ->rows(3)
                                ->columnSpanFull(),

                            Textarea::make('description')
                                ->label('مقدمه مقاله')
                                ->rows(5)
                                ->columnSpanFull(),

                            Repeater::make('sections')
                                ->label('بخش‌های مقاله / فهرست کناری')
                                ->schema([
                                    TextInput::make('title')
                                        ->label('عنوان بخش')
                                        ->required()
                                        ->maxLength(255),

                                    Textarea::make('description')
                                        ->label('متن بخش')
                                        ->rows(5)
                                        ->columnSpanFull(),
                                ])
                                ->columns(2)
                                ->defaultItems(0)
                                ->collapsible()
                                ->reorderable()
                                ->addActionLabel('افزودن بخش مقاله')
                                ->columnSpanFull(),

                            Textarea::make('quote')
                                ->label('نقل‌قول برجسته')
                                ->rows(3)
                                ->columnSpanFull(),

                            TagsInput::make('takeaways')
                                ->label('Key Takeaways')
                                ->placeholder('یک نکته بنویسید و Enter بزنید')
                                ->helperText('به‌صورت آرایه‌ای از متن‌ها ذخیره می‌شود.')
                                ->default([])
                                ->columnSpanFull(),
                        ])
                        ->columns(2)
                        ->minItems(1)
                        ->collapsible()
                        ->addActionLabel('افزودن ترجمه')
                        ->itemLabel(fn (array $state): string =>
                            strtoupper($state['locale'] ?? '--') . ' | ' . ($state['title'] ?? 'بدون عنوان')),
                ])
                ->columnSpanFull(),
        ]);
    }

    private static function slugify(string $name): string
    {
        $normalized = mb_strtolower(trim($name));
        $slug = preg_replace('/[^\p{L}\p{M}\p{N}]+/u', '-', $normalized);

        return trim($slug ?? '', '-');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('شناسه')
                    ->sortable(),

                ImageColumn::make('image_1')
                    ->label('تصویر')
                    ->disk('api_public'),

                TextColumn::make('translations.title')
                    ->label('عنوان مقاله')
                    ->getStateUsing(function (Blog $record): ?string {
                        $translations = $record->translations;

                        return $translations->firstWhere('locale', app()->getLocale())?->title
                            ?? $translations->firstWhere('locale', 'en')?->title
                            ?? $translations->first()?->title;
                    })
                    ->searchable(query: fn (Builder $query, string $search): Builder =>
                    $query->whereHas('translations', fn (Builder $translations): Builder =>
                    $translations->where('title', 'like', "%{$search}%")))
                    ->placeholder('بدون ترجمه'),

                TextColumn::make('published_at')
                    ->label('انتشار')
                    ->dateTime('Y/m/d H:i')
                    ->sortable()
                    ->placeholder('تعیین نشده'),

                IconColumn::make('is_active')
                    ->label('فعال')
                    ->boolean()
                    ->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->recordActions([
                EditAction::make()->label('ویرایش'),
                DeleteAction::make()->label('حذف')->requiresConfirmation(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('حذف مقالات انتخاب‌شده'),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBlogs::route('/'),
            'create' => Pages\CreateBlog::route('/create'),
            'edit' => Pages\EditBlog::route('/{record}/edit'),
        ];
    }
}
