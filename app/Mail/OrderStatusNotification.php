<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderStatusNotification extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Order $order,
        public string $notificationType,
    ) {
        $this->order->loadMissing('items');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectLine(),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.orders.status',
            with: [
                'order' => $this->order,
                'headline' => $this->headline(),
                'intro' => $this->intro(),
            ],
        );
    }

    private function subjectLine(): string
    {
        return match ($this->notificationType) {
            'processing' => 'Your Lumina Beauty order is processing',
            'completed' => 'Your Lumina Beauty order is completed',
            default => 'We received your Lumina Beauty order',
        };
    }

    private function headline(): string
    {
        return match ($this->notificationType) {
            'processing' => 'Order Processing',
            'completed' => 'Order Completed',
            default => 'Order Received / Pending',
        };
    }

    private function intro(): string
    {
        return match ($this->notificationType) {
            'processing' => 'Your order is now being prepared by the Lumina Beauty team.',
            'completed' => 'Your order has been completed. Thank you for shopping with Lumina Beauty.',
            default => 'Thank you for your order. We have received it and it is pending review.',
        };
    }
}
