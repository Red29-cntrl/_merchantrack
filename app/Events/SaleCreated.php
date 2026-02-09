<?php

namespace App\Events;

use App\Sale;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SaleCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $sale;
    public $user_role; // 'admin' or 'staff'
    public $user_name; // Name of user who performed the action

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Sale $sale, $user_role = null, $user_name = null)
    {
        // Store only the sale ID to avoid serialization issues with relationships
        // We use broadcastWith() to send the actual data
        $this->sale = $sale->withoutRelations(); // Get sale without relationships
        $this->user_role = $user_role ?? (auth()->check() ? auth()->user()->role : 'staff');
        $this->user_name = $user_name ?? (auth()->check() ? auth()->user()->name : 'Staff');
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new Channel('sales');
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'sale.created';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        // Return only simple data to avoid serialization issues
        // Reload sale fresh to get current data
        $sale = Sale::find($this->sale->id);
        if (!$sale) {
            return [];
        }
        
        $sale->load('user');
        
        return [
            'sale_id' => $sale->id,
            'sale_number' => $sale->sale_number,
            'total' => (float) $sale->total,
            'payment_method' => (string) $sale->payment_method,
            'cashier_name' => (string) ($sale->user->name ?? 'Unknown'),
            'user_name' => (string) $this->user_name,
            'created_at' => $sale->created_at->toDateTimeString(),
            'user_role' => (string) $this->user_role,
        ];
    }
}
