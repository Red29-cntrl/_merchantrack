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

class ProductUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $product;
    public $action; // 'created', 'updated', 'deleted'
    public $user_role; // 'admin' or 'staff'
    public $user_name; // Name of user who performed the action

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Product $product, $action = 'updated', $user_role = null, $user_name = null)
    {
        $this->product = $product->load('category', 'supplier');
        $this->action = $action;
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
        return new Channel('products');
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'product.updated';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        // Reload product fresh to get current data
        $product = Product::find($this->product->id);
        if (!$product) {
            return [
                'id' => $this->product->id,
                'action' => (string) $this->action,
                'user_role' => (string) $this->user_role,
            ];
        }
        
        return [
            'id' => (int) $product->id,
            'name' => (string) $product->name,
            'sku' => (string) $product->sku,
            'barcode' => (string) ($product->barcode ?? ''),
            'price' => (float) $product->price,
            'cost' => (float) ($product->cost ?? 0),
            'quantity' => (int) $product->quantity,
            'reorder_level' => (int) $product->reorder_level,
            'unit' => (string) ($product->unit ?? 'pcs'),
            'category_id' => $product->category_id ? (int) $product->category_id : null,
            'is_active' => (bool) $product->is_active,
            'action' => (string) $this->action,
            'user_role' => (string) $this->user_role,
            'user_name' => (string) $this->user_name,
        ];
    }
}
