<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BlogResource\Pages;
use App\Models\Blog;

use BackedEnum;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;

use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;

use Illuminate\Database\Eloquent\Builder;

class BlogResource extends Resource
{
    // ==========================================
    // RESOURCE CONFIGURATION
    // ==========================================

    protected static ?string $model = Blog::class;

    protected static string|BackedEnum|null $navigationIcon =
        'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Blogs';

    protected static ?string $modelLabel = 'مقاله';

    protected static ?string $pluralModelLabel = 'مقالات';

    // ==========================================
    // FORM
    // ==========================================

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([

                // ==================================
                // MAIN INFORMATION
                // ==================================

                Section::make('اطلاعات اصلی')
                    ->schema([

                        FileUpload::make('image_1')
                            ->label('تصویر اصلی مقاله')
                            ->image()
                            ->disk('api_public')
                            ->directory('blogs')
                            ->visibility('public')
                            ->imagePreviewHeight('200')
                            ->panelLayout('integrated')
                            ->uploadButtonPosition('left')
                            ->maxSize(5120)
                            ->required(),

                        FileUpload::make('image_2')
                            ->label('تصویر دوم مقاله')
                            ->image()
                            ->disk('api_public')
                            ->directory('blogs')
                            ->visibility('public'),

                        FileUpload::make('image_3')
                            ->label('تصویر سوم مقاله')
                            ->image()
                            ->disk('api_public')
                            ->directory('blogs')
                            ->visibility('public'),

                        Toggle::make('is_active')
                            ->label('فعال / غیرفعال')
                            ->default(true),

                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                // ==================================
                // TRANSLATIONS
                // ==================================

                Section::make('ترجمه‌ها')
                    ->description(
                        'محتوای هر زبان را به‌صورت جداگانه وارد کنید.'
                    )
                    ->schema([

                        Repeater::make('translations')
                            ->label('ترجمه‌های مقاله')
                            ->relationship('translations')
                            ->schema([

                                // LANGUAGE

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

                                // MAIN TITLE

                                TextInput::make('title')
                                    ->label('عنوان مقاله')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->columnSpanFull(),

                                // MAIN DESCRIPTION

                                Textarea::make('description')
                                    ->label('توضیحات مقاله')
                                    ->rows(4)
                                    ->columnSpanFull(),

                                // CONTENT SECTION 1

                                TextInput::make('title_1')
                                    ->label('عنوان 1'),

                                Textarea::make('description_1')
                                    ->label('توضیح 1')
                                    ->rows(4),

                                // CONTENT SECTION 2

                                TextInput::make('title_2')
                                    ->label('عنوان 2'),

                                Textarea::make('description_2')
                                    ->label('توضیح 2')
                                    ->rows(4),

                                // CONTENT SECTION 3

                                TextInput::make('title_3')
                                    ->label('عنوان 3'),

                                Textarea::make('description_3')
                                    ->label('توضیح 3')
                                    ->rows(4),

                                // CONTENT SECTION 4

                                TextInput::make('title_4')
                                    ->label('عنوان 4'),

                                Textarea::make('description_4')
                                    ->label('توضیح 4')
                                    ->rows(4),

                                // CONTENT SECTION 5

                                TextInput::make('title_5')
                                    ->label('عنوان 5'),

                                Textarea::make('description_5')
                                    ->label('توضیح 5')
                                    ->rows(4),

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
                                    filled($state['title'] ?? null)
                                        ? $state['title']
                                        : 'بدون عنوان'
                                    )
                            ),

                    ])
                    ->columnSpanFull(),

            ]);
    }

    // ==========================================
    // TABLE
    // ==========================================

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                // ARTICLE TITLE

                TextColumn::make('translations.title')
                    ->label('عنوان مقاله')
                    ->getStateUsing(
                        fn (Blog $record): ?string =>
                        $record->translation()?->title
                    )
                    ->searchable(
                        query: fn (
                            Builder $query,
                            string $search
                        ): Builder =>
                        $query->whereHas(
                            'translations',
                            fn (Builder $translationQuery): Builder =>
                            $translationQuery->where(
                                'title',
                                'like',
                                "%{$search}%"
                            )
                        )
                    )
                    ->sortable(
                        query: fn (
                            Builder $query,
                            string $direction
                        ): Builder =>
                        static::sortByTranslationTitle(
                            $query,
                            $direction
                        )
                    ),

                // ARTICLE STATUS

                IconColumn::make('is_active')
                    ->label('وضعیت')
                    ->boolean()
                    ->sortable(),

                // CREATED AT

                TextColumn::make('created_at')
                    ->label('تاریخ ایجاد')
                    ->dateTime('Y/m/d H:i')
                    ->sortable(),

            ])

            // ==================================
            // RECORD ACTIONS
            // ==================================

            ->recordActions([

                EditAction::make()
                    ->label('ویرایش')
                    ->icon('heroicon-o-pencil-square'),

                DeleteAction::make()
                    ->label('حذف')
                    ->icon('heroicon-o-trash')
                    ->requiresConfirmation(),

            ])

            // ==================================
            // BULK ACTIONS
            // ==================================

            ->toolbarActions([

                BulkActionGroup::make([

                    DeleteBulkAction::make()
                        ->label('حذف مقالات انتخاب‌شده')
                        ->requiresConfirmation(),

                ]),

            ]);
    }

    // ==========================================
    // SORT BY TRANSLATION TITLE
    // ==========================================

    private static function sortByTranslationTitle(
        Builder $query,
        string $direction
    ): Builder {
        $relationship = $query->getModel()->translations();

        $related = $relationship->getRelated();

        $translationQuery = $related
            ->newQuery()
            ->select('title')
            ->whereColumn(
                $relationship->getQualifiedForeignKeyName(),
                $query->getModel()->getQualifiedKeyName()
            )
            ->orderByRaw(
                'CASE WHEN locale = ? THEN 0 ELSE 1 END',
                [app()->getLocale()]
            )
            ->orderBy('locale')
            ->limit(1);

        return $query->orderBy(
            $translationQuery,
            $direction
        );
    }

    // ==========================================
    // RESOURCE PAGES
    // ==========================================

    public static function getPages(): array
    {
        return [

            'index' => Pages\ListBlogs::route('/'),

            'create' => Pages\CreateBlog::route('/create'),

            'edit' => Pages\EditBlog::route('/{record}/edit'),

        ];
    }
}
