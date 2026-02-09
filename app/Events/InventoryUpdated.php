<?php

namespace App\Events;

use App\Product;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InventoryUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $product;
    public $quantity;
    public $movement_type;
    public $user_role; // 'admin' or 'staff'
    public $user_name; // Name of user who performed the action

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Product $product, $movement_type = 'adjustment', $user_role = null, $user_name = null)
    {
        // Store only the product ID to avoid serialization issues with relationships
        // We use broadcastWith() to send the actual data
        $this->product = $product->withoutRelations(); // Get product without relationships
        $this->quantity = (int) $product->quantity;
        $this->movement_type = (string) $movement_type;
        $this->user_role = $user_role ?? (auth()->check() ? auth()->user()->role : 'admin');
        $this->user_name = $user_name ?? (auth()->check() ? auth()->user()->name : 'Admin');
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new Channel('inventory');
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'inventory.updated';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        // Return only simple data to avoid serialization issues
        // Reload product fresh to get current data
        $product = Product::find($this->product->id);
        if (!$product) {
            return [];
        }
        
        return [
            'product_id' => (int) $product->id,
            'product_name' => (string) $product->name,
            'quantity' => (int) $this->quantity,
            'movement_type' => (string) $this->movement_type,
            'user_role' => (string) $this->user_role,
            'user_name' => (string) $this->user_name,
        ];
    }
}
