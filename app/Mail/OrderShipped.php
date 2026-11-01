<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderShipped extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Your Kayaa order {$this->order->reference} is on its way");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.order-shipped', with: [
            'order' => $this->order,
            'trackUrl' => $this->order->trackUrl(),
        ]);
    }
}
