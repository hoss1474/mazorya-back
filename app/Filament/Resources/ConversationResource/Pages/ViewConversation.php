<?php

namespace App\Filament\Resources\ConversationResource\Pages;

use App\Filament\Resources\ConversationResource;
use App\Models\Message;
use Filament\Infolists\Components\ViewEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Livewire\WithFileUploads;

class ViewConversation extends ViewRecord
{
    use WithFileUploads;

    protected static string $resource = ConversationResource::class;

    public $messageText = '';

    public $attachment = null;

    public $perPage = 50;

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->schema([
                    ViewEntry::make('chat_view')
                        ->view('conversation.view-chat')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public function getChatMessagesProperty()
    {
        return Message::where('conversation_id', $this->record->id)
            ->latest()
            ->take($this->perPage)
            ->get()
            ->reverse();
    }

    public function sendReply()
    {
        $text = trim($this->messageText);

        if (empty($text) && !$this->attachment) {
            return;
        }

        $path = null;

        if ($this->attachment) {
            $path = $this->attachment->store(
                'chat-attachments',
                'api_public'
            );
        }

        Message::create([
            'conversation_id' => $this->record->id,
            'visitor_id' => $this->record->visitor_id,
            'sender' => 'admin',
            'message' => $text,
            'file_path' => $path,
        ]);

        $this->reset(['messageText', 'attachment']);

        $this->dispatch('scroll-to-bottom');
    }
}
