<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectResource\Pages;
use App\Models\Project;
use Filament\Forms;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'پروژه‌ها';
    protected static ?string $modelLabel = 'پروژه';
    protected static ?string $pluralModelLabel = 'پروژه‌ها';

    public static function form(Form $form): Form
    {
        return $form->schema([

            /* ================= داده‌های ثابت پروژه ================= */
            Section::make('اطلاعات اصلی')
                ->schema([
                    FileUpload::make('image')
                        ->label('تصویر اصلی')
                        ->image()
                        ->disk('api_public')
                        ->directory('projects')
                        ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                        ->required(),

                    FileUpload::make('image2')
                        ->label('تصویر دوم')
                        ->image()
                        ->disk('api_public')
                        ->directory('projects')
                        ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp']),

                    Forms\Components\TextInput::make('url')
                        ->label('آدرس سایت')
                        ->url()
                        ->maxLength(255),

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
                                ->columnSpanFull(),

                            Forms\Components\TextInput::make('name')
                                ->label('نام پروژه')
                                ->required(),

                            Forms\Components\Textarea::make('description')
                                ->label('توضیحات کلی'),

                            Forms\Components\TextInput::make('title_1')
                                ->label('عنوان 1'),
                            Forms\Components\Textarea::make('description_1')
                                ->label('توضیح 1'),

                            Forms\Components\TextInput::make('title_2')
                                ->label('عنوان 2'),
                            Forms\Components\Textarea::make('description_2')
                                ->label('توضیح 2'),

                            Forms\Components\TextInput::make('title_3')
                                ->label('عنوان 3'),
                            Forms\Components\Textarea::make('description_3')
                                ->label('توضیح 3'),

                            Forms\Components\TextInput::make('title_4')
                                ->label('عنوان 4'),
                            Forms\Components\Textarea::make('description_4')
                                ->label('توضیح 4'),
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
                Tables\Columns\TextColumn::make('id')
                    ->sortable(),

                Tables\Columns\TextColumn::make('translations.name')
                    ->label('نام پروژه')
                    ->getStateUsing(fn ($record) => $record->translation()?->name)
                    ->searchable(),

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
            'index'  => Pages\ListProjects::route('/'),
            'create' => Pages\CreateProject::route('/create'),
            'edit'   => Pages\EditProject::route('/{record}/edit'),
        ];
    }
}
