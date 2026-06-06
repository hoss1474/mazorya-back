<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BlogResource\Pages;
use App\Models\Blog;
use Filament\Forms;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;

class BlogResource extends Resource
{
    protected static ?string $model = Blog::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'مقالاdت';
    protected static ?string $modelLabel = 'مقاله';
    protected static ?string $pluralModelLabel = 'مقالاhhت';

    public static function form(Form $form): Form
    {
        return $form->schema([

            /* ================= اطلاعات اصلی ================= */
            Section::make('اطلاعات اصلی')
                ->schema([
                    FileUpload::make('image_1')
                        ->label('تصویر اصلی مقاله')
                        ->image()
                        ->disk('api_public')
                        ->directory('blogs')
                        ->required(),

                    FileUpload::make('image_2')
                        ->label('تصویر دوم مقاله')
                        ->image()
                        ->disk('api_public')
                        ->directory('blogs'),

                    FileUpload::make('image_3')
                        ->label('تصویر سوم مقاله')
                        ->image()
                        ->disk('api_public')
                        ->directory('blogs'),

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

                            Forms\Components\TextInput::make('title')
                                ->label('عنوان مقاله')
                                ->required(),

                            Forms\Components\Textarea::make('description')
                                ->label('توضیحات مقاله'),

                            // بلاک‌های محتوا
                            Forms\Components\TextInput::make('title_1')->label('عنوان 1'),
                            Forms\Components\Textarea::make('description_1')->label('توضیح 1'),

                            Forms\Components\TextInput::make('title_2')->label('عنوان 2'),
                            Forms\Components\Textarea::make('description_2')->label('توضیح 2'),

                            Forms\Components\TextInput::make('title_3')->label('عنوان 3'),
                            Forms\Components\Textarea::make('description_3')->label('توضیح 3'),

                            Forms\Components\TextInput::make('title_4')->label('عنوان 4'),
                            Forms\Components\Textarea::make('description_4')->label('توضیح 4'),

                            Forms\Components\TextInput::make('title_5')->label('عنوان 5'),
                            Forms\Components\Textarea::make('description_5')->label('توضیح 5'),
                        ])
                        ->columns(2)
                        ->minItems(1)
                        ->collapsible()
                ])
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('translations.title')
                    ->label('عنوان')
                    ->getStateUsing(fn($record) => $record->translation()?->title)
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BooleanColumn::make('is_active')
                    ->label('وضعیت'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاریخ ایجاد')
                    ->dateTime()
                    ->sortable(),
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
            'index' => Pages\ListBlogs::route('/'),
            'create' => Pages\CreateBlog::route('/create'),
            'edit' => Pages\EditBlog::route('/{record}/edit'),
        ];
    }
}
