<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectResource\Pages;
use App\Models\Project;
use App\Models\ProjectTranslation;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Unique;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Projects';

    protected static ?string $modelLabel = 'پروژه';

    protected static ?string $pluralModelLabel = 'پروژه‌ها';

    private const CATEGORIES = [
        'website' => 'وب‌سایت',
        'ui-ux' => 'UI / UX',
        'social-media' => 'شبکه‌های اجتماعی',
        'prompts' => 'پرامپت‌ها',
        'graphic' => 'طراحی گرافیک',
        'other' => 'سایر',
    ];

    private const LANGUAGES = [
        'fa' => 'فارسی',
        'en' => 'English',
        'de' => 'Deutsch',
        'fr' => 'Français',
        'es' => 'Español',
        'ar' => 'العربية',
    ];

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('اطلاعات اصلی')
                ->description('اطلاعات مشترک پروژه در همه زبان‌ها')
                ->schema([
                    Select::make('category')
                        ->label('دسته‌بندی')
                        ->options(self::CATEGORIES)
                        ->required()
                        ->native(false)
                        ->live(),

                    TextInput::make('client')
                        ->label('کارفرما')
                        ->maxLength(255),

                    TextInput::make('year')
                        ->label('سال پروژه')
                        ->integer()
                        ->minValue(1900)
                        ->maxValue(2100),

                    TextInput::make('url')
                        ->label('لینک اصلی پروژه')
                        ->url()
                        ->maxLength(255)
                        ->placeholder('https://example.com'),

                    Toggle::make('is_active')
                        ->label('فعال / غیرفعال')
                        ->default(true),

                    Toggle::make('is_marked')
                        ->label('Mark Project')
                        ->default(false),
                ])
                ->columns(2)
                ->columnSpanFull(),

            Section::make('تصاویر پروژه')
                ->schema([
                    static::imageUpload('image', 'تصویر اصلی')->required(),
                    static::imageUpload('image2', 'تصویر دوم'),

                    FileUpload::make('gallery')
                        ->label('گالری تصاویر')
                        ->disk('api_public')
                        ->directory('projects')
                        ->visibility('public')
                        ->image()
                        ->multiple()
                        ->reorderable()
                        ->appendFiles()
                        ->maxFiles(30)
                        ->maxSize(5120)
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->columnSpanFull(),

            Section::make('تکنولوژی‌های وب‌سایت')
                ->schema([
                    TagsInput::make('technologies')
                        ->label('تکنولوژی‌ها')
                        ->suggestions([
                            'HTML', 'CSS', 'JavaScript', 'TypeScript',
                            'React', 'Next.js', 'Vue', 'PHP', 'Laravel',
                            'WordPress', 'MySQL', 'Tailwind CSS',
                        ])
                        ->default([]),
                ])
                ->visible(fn (Get $get): bool => $get('category') === 'website')
                ->columnSpanFull(),

            Section::make('اطلاعات اختصاصی دسته‌بندی')
                ->description('فیلدها بر اساس دسته‌بندی انتخاب‌شده نمایش داده می‌شوند.')
                ->schema([
                    Group::make()
                        ->statePath('category_data')
                        ->schema(static::categoryDataFields())
                        ->columns(2),
                ])
                ->visible(fn (Get $get): bool => in_array(
                    $get('category'),
                    ['ui-ux', 'social-media', 'prompts', 'graphic'],
                    true
                ))
                ->columnSpanFull(),

            Section::make('ترجمه‌های پروژه')
                ->description('نام، Slug، توضیحات و محتوای مخصوص هر زبان')
                ->schema([
                    Repeater::make('translations')
                        ->label('زبان‌ها')
                        ->relationship('translations')
                        ->schema(static::translationFields())
                        ->minItems(1)
                        ->maxItems(6)
                        ->defaultItems(1)
                        ->collapsible()
                        ->addActionLabel('افزودن زبان')
                        ->itemLabel(fn (array $state): string =>
                            strtoupper((string) ($state['lang'] ?? '--'))
                            . ' | '
                            . (filled($state['name'] ?? null)
                                ? (string) $state['name']
                                : 'پروژه بدون نام')
                        )
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),
        ]);
    }

    private static function imageUpload(string $name, string $label): FileUpload
    {
        return FileUpload::make($name)
            ->label($label)
            ->disk('api_public')
            ->directory('projects')
            ->visibility('public')
            ->image()
            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
            ->imagePreviewHeight('200')
            ->panelLayout('integrated')
            ->maxSize(5120);
    }

    /**
     * The fields below are saved as keys inside projects.category_data (JSON).
     * `../category` goes from category_data to the main project form state.
     */
    private static function categoryDataFields(): array
    {
        return [
            TextInput::make('prototype_url')
                ->label('لینک پروتوتایپ')
                ->url()
                ->maxLength(255)
                ->visible(fn (Get $get): bool => $get('../category') === 'ui-ux'),

            TextInput::make('figma_url')
                ->label('لینک Figma')
                ->url()
                ->maxLength(255)
                ->visible(fn (Get $get): bool => $get('../category') === 'ui-ux'),

            TextInput::make('campaign_url')
                ->label('لینک کمپین')
                ->url()
                ->maxLength(255)
                ->visible(fn (Get $get): bool => $get('../category') === 'social-media'),

            TextInput::make('purchase_url')
                ->label('لینک خرید')
                ->url()
                ->maxLength(255)
                ->visible(fn (Get $get): bool => $get('../category') === 'prompts'),

            TextInput::make('download_url')
                ->label('لینک دانلود')
                ->url()
                ->maxLength(255)
                ->visible(fn (Get $get): bool => $get('../category') === 'prompts'),

            TagsInput::make('design_tools')
                ->label('ابزارهای طراحی')
                ->default([])
                ->columnSpanFull()
                ->visible(fn (Get $get): bool => in_array(
                    $get('../category'), ['ui-ux', 'graphic'], true
                )),

            TagsInput::make('platforms')
                ->label('پلتفرم‌های کمپین')
                ->default([])
                ->columnSpanFull()
                ->visible(fn (Get $get): bool => $get('../category') === 'social-media'),

            TagsInput::make('compatible_tools')
                ->label('ابزارهای سازگار')
                ->default([])
                ->columnSpanFull()
                ->visible(fn (Get $get): bool => $get('../category') === 'prompts'),
        ];
    }

    /**
     * translations has a HasMany relationship with projects.
     * sections and category_content are JSON columns on project_translations.
     */
    private static function translationFields(): array
    {
        return [
            Select::make('lang')
                ->label('زبان')
                ->options(self::LANGUAGES)
                ->required()
                ->native(false)
                ->disableOptionsWhenSelectedInSiblingRepeaterItems(),

            TextInput::make('name')
                ->label('نام پروژه')
                ->required()
                ->maxLength(255)
                ->live(onBlur: true)
                ->afterStateUpdated(function (
                    Get $get,
                    Set $set,
                    ?string $old,
                    ?string $state
                ): void {
                    $currentSlug = (string) ($get('slug') ?? '');
                    $previousAutoSlug = ProjectTranslation::slugify($old ?? '');

                    // Continue tracking the title only while the slug has not been edited manually.
                    if ($currentSlug !== '' && $currentSlug !== $previousAutoSlug) {
                        return;
                    }

                    $set('slug', ProjectTranslation::slugify($state ?? ''));
                }),

            TextInput::make('slug')
                ->label('Slug')
                ->required()
                ->maxLength(255)
                ->regex('/^[\p{L}\p{M}\p{N}]+(?:-[\p{L}\p{M}\p{N}]+)*$/u')
                ->unique(
                    table: ProjectTranslation::class,
                    column: 'slug',
                    ignorable: fn ($record) =>
                    $record instanceof ProjectTranslation ? $record : null,
                    modifyRuleUsing: fn (Unique $rule, Get $get): Unique =>
                    $rule->where('lang', $get('lang'))
                )
                ->helperText('در هر زبان یکتا است؛ می‌توان برای چند زبان از یک Slug استفاده کرد.'),

            Textarea::make('description')
                ->label('توضیحات پروژه')
                ->rows(4)
                ->columnSpanFull(),

            Repeater::make('sections')
                ->label('بخش‌های محتوایی')
                ->schema([
                    TextInput::make('title')
                        ->label('عنوان بخش')
                        ->maxLength(255),
                    Textarea::make('description')
                        ->label('توضیح بخش')
                        ->rows(3),
                ])
                ->columns(2)
                ->defaultItems(0)
                ->collapsible()
                ->addActionLabel('افزودن بخش')
                ->columnSpanFull(),

            Group::make()
                ->statePath('category_content')
                ->schema([
                    Repeater::make('process')
                        ->label('مراحل طراحی UI/UX')
                        ->schema([
                            TextInput::make('title')
                                ->label('عنوان مرحله')
                                ->required()
                                ->maxLength(255),
                            Textarea::make('description')
                                ->label('توضیح مرحله')
                                ->rows(3),
                        ])
                        ->defaultItems(0)
                        ->collapsible()
                        ->addActionLabel('افزودن مرحله')
                        ->columnSpanFull()
                        ->visible(fn (Get $get): bool =>
                            $get('../../../category') === 'ui-ux'),

                    Textarea::make('prompt_preview')
                        ->label('پیش‌نمایش پرامپت')
                        ->rows(5)
                        ->columnSpanFull()
                        ->visible(fn (Get $get): bool =>
                            $get('../../../category') === 'prompts'),

                    TagsInput::make('features')
                        ->label('ویژگی‌های پروژه')
                        ->default([])
                        ->columnSpanFull()
                        ->visible(fn (Get $get): bool =>
                            $get('../../../category') === 'other'),
                ])
                ->columnSpanFull(),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('شناسه')
                    ->sortable(),

                ImageColumn::make('image')
                    ->label('تصویر')
                    ->disk('api_public'),

                TextColumn::make('translations.name')
                    ->label('نام پروژه')
                    ->getStateUsing(fn (Project $record): ?string =>
                        $record->translations->firstWhere('lang', 'en')?->name
                        ?? $record->translations->firstWhere('lang', 'fa')?->name
                        ?? $record->translations->first()?->name
                    )
                    ->searchable(
                        query: fn (Builder $query, string $search): Builder =>
                        $query->whereHas('translations',
                            fn (Builder $subquery): Builder =>
                            $subquery->where('name', 'like', "%{$search}%")
                        )
                    )
                    ->placeholder('بدون ترجمه'),

                TextColumn::make('category')
                    ->label('دسته‌بندی')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string =>
                        self::CATEGORIES[$state ?? ''] ?? ($state ?? 'نامشخص')),

                TextColumn::make('client')
                    ->label('کارفرما')
                    ->searchable(),

                TextColumn::make('year')
                    ->label('سال')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('فعال')
                    ->boolean(),

                IconColumn::make('is_marked')
                    ->label('منتخب')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label('تاریخ ایجاد')
                    ->dateTime('Y/m/d H:i')
                    ->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->recordActions([
                EditAction::make()->label('ویرایش'),
                DeleteAction::make()->label('حذف')->requiresConfirmation(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('حذف گروهی')
                        ->requiresConfirmation(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProjects::route('/'),
            'create' => Pages\CreateProject::route('/create'),
            'edit' => Pages\EditProject::route('/{record}/edit'),
        ];
    }
}
