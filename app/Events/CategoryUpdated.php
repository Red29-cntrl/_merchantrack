<?php

namespace App\Events;

use App\Category;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CategoryUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $category;
    public $action; // 'created', 'updated', 'deleted'
    public $user_role; // 'admin' or 'staff'
    public $user_name; // Name of user who performed the action

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Category $category, $action = 'updated', $user_role = null, $user_name = null)
    {
        // Store only the category ID to avoid serialization issues
        $this->category = $category->withoutRelations();
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
        return new Channel('categories');
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'category.updated';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        // Reload category fresh to get current data
        $category = Category::find($this->category->id);
        if (!$category) {
            return [
                'id' => $this->category->id,
                'action' => $this->action,
            ];
        }
        
        return [
            'id' => (int) $category->id,
            'name' => (string) $category->name,
            'description' => (string) ($category->description ?? ''),
            'action' => (string) $this->action,
            'user_role' => (string) $this->user_role,
            'user_name' => (string) $this->user_name,
        ];
    }
}
