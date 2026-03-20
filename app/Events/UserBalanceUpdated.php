<?php

namespace App\Events;

use App\Transaction;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use Carbon\Carbon;

class UserBalanceUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user;
    public $amount;
    public $transaction;
    public $date_transaction;

    /**
     * Create a new event instance.
     *
     * @return void
     */

    public function __construct(Transaction $transaction)
    {
        $date_transaction = Carbon::now();
        $this->user = User::find($transaction->user_id);
        $this->amount = $transaction->amount;
        $this->transaction = $transaction;
        $this->date_transaction = $date_transaction;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
