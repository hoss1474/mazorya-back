<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectResource\Pages;
use App\Models\Project;

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
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;

use Filament\Tables\Table;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;

use Illuminate\Database\Eloquent\Builder;

class ProjectResource extends Resource
{
    /*
    |--------------------------------------------------------------------------
    | Resource Configuration
    |--------------------------------------------------------------------------
    */

    protected static ?string $model = Project::class;

    protected static string|BackedEnum|null $navigationIcon =
        'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Projects';

    protected static ?string $modelLabel = 'پروژه';

    protected static ?string $pluralModelLabel = 'پروژه‌ها';

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
                | Project Information
                |--------------------------------------------------------------------------
                */

                Section::make('اطلاعات اصلی')
                    ->description('اطلاعات عمومی و تصاویر پروژه')
                    ->schema([

                        // Category

                        Select::make('category')
                            ->label('دسته‌بندی')
                            ->options([
                                'Website' => 'وبسایت',
                                'UI.UX' => 'UI / UX',
                                'SEO' => 'سئو',
                                'Graphic' => 'گرافیک',
                                'Social Media' => 'سوشال مدیا',
                            ])
                            ->required()
                            ->native(false)
                            ->columnSpanFull(),

                        // Main Image

                        static::projectImageUpload(
                            'image',
                            'تصویر اصلی'
                        )
                            ->required(),

                        // Secondary Image

                        static::projectImageUpload(
                            'image2',
                            'تصویر دوم'
                        ),

                        // Website URL

                        TextInput::make('url')
                            ->label('آدرس سایت')
                            ->url()
                            ->maxLength(255)
                            ->placeholder('https://example.com')
                            ->columnSpanFull(),

                        // Status

                        Toggle::make('is_active')
                            ->label('فعال / غیرفعال')
                            ->default(true)
                            ->columnSpanFull(),

                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                /*
                |--------------------------------------------------------------------------
                | Project Translations
                |--------------------------------------------------------------------------
                */

                Section::make('ترجمه‌ها')
                    ->description(
                        'برای هر زبان، اطلاعات پروژه را جداگانه وارد کنید.'
                    )
                    ->schema([

                        Repeater::make('translations')
                            ->label('ترجمه‌های پروژه')
                            ->relationship('translations')
                            ->schema([

                                // Language

                                Select::make('lang')
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

                                // Project Name

                                TextInput::make('name')
                                    ->label('نام پروژه')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->columnSpanFull(),

                                // Main Description

                                Textarea::make('description')
                                    ->label('توضیحات کلی')
                                    ->rows(4)
                                    ->columnSpanFull(),

                                /*
                                |----------------------------
                                | Content Section 1
                                |----------------------------
                                */

                                TextInput::make('title_1')
                                    ->label('عنوان ۱')
                                    ->maxLength(255),

                                Textarea::make('description_1')
                                    ->label('توضیح ۱')
                                    ->rows(4),

                                /*
                                |----------------------------
                                | Content Section 2
                                |----------------------------
                                */

                                TextInput::make('title_2')
                                    ->label('عنوان ۲')
                                    ->maxLength(255),

                                Textarea::make('description_2')
                                    ->label('توضیح ۲')
                                    ->rows(4),

                                /*
                                |----------------------------
                                | Content Section 3
                                |----------------------------
                                */

                                TextInput::make('title_3')
                                    ->label('عنوان ۳')
                                    ->maxLength(255),

                                Textarea::make('description_3')
                                    ->label('توضیح ۳')
                                    ->rows(4),

                                /*
                                |----------------------------
                                | Content Section 4
                                |----------------------------
                                */

                                TextInput::make('title_4')
                                    ->label('عنوان ۴')
                                    ->maxLength(255),

                                Textarea::make('description_4')
                                    ->label('توضیح ۴')
                                    ->rows(4),

                            ])
                            ->columns(2)
                            ->minItems(1)
                            ->collapsible()
                            ->addActionLabel('افزودن ترجمه')
                            ->itemLabel(
                                fn (array $state): string =>
                                    strtoupper(
                                        $state['lang'] ?? '--'
                                    )
                                    . ' | '
                                    . (
                                    filled($state['name'] ?? null)
                                        ? $state['name']
                                        : 'پروژه بدون نام'
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

    private static function projectImageUpload(
        string $field,
        string $label
    ): FileUpload {
        return FileUpload::make($field)
            ->label($label)
            ->image()
            ->disk('api_public')
            ->directory('projects')
            ->visibility('public')
            ->acceptedFileTypes([
                'image/png',
                'image/jpeg',
                'image/webp',
            ])
            ->imagePreviewHeight('200')
            ->panelLayout('integrated');
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

                // ID

                TextColumn::make('id')
                    ->label('شناسه')
                    ->sortable(),

                // Project Name

                TextColumn::make('translations.name')
                    ->label('نام پروژه')
                    ->getStateUsing(
                        fn (Project $record): ?string =>
                        $record->translation()?->name
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
                                'name',
                                'like',
                                "%{$search}%"
                            )
                        )
                    )
                    ->placeholder('بدون ترجمه'),

                // Status

                IconColumn::make('is_active')
                    ->label('وضعیت')
                    ->boolean()
                    ->sortable(),

                // Created At

                TextColumn::make('created_at')
                    ->label('تاریخ ایجاد')
                    ->dateTime('Y/m/d H:i')
                    ->sortable(),

            ])

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
                        ->label('حذف پروژه‌های انتخاب‌شده')
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

            'index' => Pages\ListProjects::route('/'),

            'create' => Pages\CreateProject::route('/create'),

            'edit' => Pages\EditProject::route('/{record}/edit'),

        ];
    }
}
