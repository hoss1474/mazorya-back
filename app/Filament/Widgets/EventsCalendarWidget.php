<?php

namespace App\Filament\Widgets;

use App\Models\Event;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DateTimePicker;
use Illuminate\Database\Eloquent\Model;
use Saade\FilamentFullCalendar\Actions\EditAction;
use Saade\FilamentFullCalendar\Actions\CreateAction;
use Saade\FilamentFullCalendar\Actions\DeleteAction;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;
use Illuminate\Support\Carbon;

class EventsCalendarWidget extends FullCalendarWidget
{
    protected static ?int $sort = 5;

    protected int | string | array $columnSpan = 'full';

    public Model | string | null $model = Event::class;

    public function config(): array
    {
        return [
            'height' => '600px',
            'headerToolbar' => [
                'left' => 'prev,next today',
                'center' => 'title',
                'right' => 'dayGridMonth,timeGridWeek,timeGridDay',
            ],
            'locale' => 'fa',
            'firstDay' => 6,
        ];
    }

    /**
     * دریافت رویدادهای تقویم
     */
    public function fetchEvents(array $fetchInfo): array
    {
        return Event::query()
            ->where('start_date', '<', $fetchInfo['end'])
            ->where('end_date', '>', $fetchInfo['start'])
            ->get()
            ->map(fn (Event $event) => [
                'id' => $event->id,
                'title' => $event->title,
                'start' => $event->start_date->toIso8601String(),
                'end' => $event->end_date->toIso8601String(),
                'color' => $event->type === 'meeting'
                    ? '#f59e0b'
                    : '#3b82f6',
            ])
            ->toArray();
    }

    /**
     * ساختار فرم ساخت و ویرایش رویداد
     */
    public function getFormSchema(): array
    {
        return [
            TextInput::make('title')
                ->label('عنوان رویداد')
                ->required()
                ->placeholder('مثلاً: جلسه فنی'),

            Select::make('type')
                ->label('نوع رویداد')
                ->options([
                    'meeting' => 'جلسه',
                    'task' => 'تسک',
                    'holiday' => 'تعطیلات',
                    'etc' => 'غیره',
                ])
                ->default('meeting')
                ->required(),

            DateTimePicker::make('start_date')
                ->label('تاریخ و زمان شروع')
                ->required()
                ->seconds(false),

            DateTimePicker::make('end_date')
                ->label('تاریخ و زمان پایان')
                ->required()
                ->seconds(false),

            Textarea::make('description')
                ->label('توضیحات تکمیلی')
                ->rows(3)
                ->columnSpanFull(),
        ];
    }

    /**
     * دکمه ساخت رویداد
     */
    protected function headerActions(): array
    {
        return [
            CreateAction::make()
                ->label('رویداد جدید')
                ->mountUsing(function (Schema $schema, array $arguments): void {
                    $schema->fill([
                        'start_date' => isset($arguments['start'])
                            ? Carbon::parse($arguments['start'])
                            : now(),

                        'end_date' => isset($arguments['end'])
                            ? Carbon::parse($arguments['end'])
                            : now()->addHour(),

                        'type' => 'meeting',
                    ]);
                }),
        ];
    }

    /**
     * اکشن‌های ویرایش و حذف
     */
    protected function modalActions(): array
    {
        return [
            EditAction::make()
                ->mountUsing(function (
                    Event $record,
                    Schema $schema,
                    array $arguments
                ): void {
                    $schema->fill([
                        'title' => $record->title,
                        'type' => $record->type,
                        'start_date' => $record->start_date,
                        'end_date' => $record->end_date,
                        'description' => $record->description,
                    ]);
                }),

            DeleteAction::make(),
        ];
    }
}
