<?php

namespace App\Mail;

use App\Models\Order;
use App\Helpers\OrderHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderCompleted extends Mailable
{
    use Queueable, SerializesModels;

    public Order $order;
    public string $packageName;
    public string $formattedAmount;
    public ?string $expiresAt;

    public function __construct(Order $order)
    {
        $this->order = $order;
        $this->packageName = OrderHelper::displayPackageName($order->hours);
        $this->formattedAmount = OrderHelper::formatMoney($order->amount);
        $this->expiresAt = $order->expires_at ? $order->expires_at->format('d/m/Y H:i:s') : null;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '🔑 Thông tin tài khoản UnlockTool - Đơn ' . $this->order->tracking_code,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.order-completed',
        );
    }
}
