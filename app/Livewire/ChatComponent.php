<?php

namespace App\Livewire;

use App\Events\MessageSendEvent;
use App\Models\Message;
use App\Models\User;
use Livewire\Attributes\On;
use Livewire\Component;

class ChatComponent extends Component
{

    public $otherPerson;
    public $receiver_id;
    public $sender_id;
    public $message = '';
    public $messages = [];

    public function mount($userid)
    {
        $this->receiver_id = $userid;
        $this->sender_id = auth()->user()->id;
        $this->otherPerson = User::where('id', $userid)->first();

        $chatMessages = Message::where(function ($query) {
            $query->where('sender_id', $this->sender_id)->where('receiver_id', $this->receiver_id);
        })->orWhere(function ($query) {
            $query->where('sender_id', $this->receiver_id)->where('receiver_id', $this->sender_id);
        })->with('sender:id,name', 'receiver:id,name')->get();

        foreach ($chatMessages as $key => $chatMessage) {
            $this->appendChatMessages($chatMessage);
        }
    }

    public function sendMessage()
    {
        $messageToBeSend = new Message();
        $messageToBeSend->sender_id = $this->sender_id;
        $messageToBeSend->receiver_id = $this->receiver_id;
        $messageToBeSend->message = $this->message;
        $messageToBeSend->save();
        $this->appendChatMessages($messageToBeSend);
        broadcast(new MessageSendEvent($messageToBeSend))->toOthers();
        $this->message = '';
    }

    #[On('echo-private:chat-channel.{sender_id},MessageSendEvent')]
    public function listenChat($event)
    {
        $chatMessageListen = Message::whereId($event['message']['id'])->with('sender:id,name', 'receiver:id,name')->first();
        $this->appendChatMessages($chatMessageListen);
    }


    public function appendChatMessages($chatMessage)
    {
        $this->messages[] = [
            'id' => $chatMessage->id,
            'message' => $chatMessage->message,
            'sender' => $chatMessage->sender->name,
            'receiver' => $chatMessage->receiver->name,
            'deliverAt' => $chatMessage->created_at->format('H:i:s'),
        ];
    }

    public function render()
    {
        return view('livewire.chat-component');
    }
}
