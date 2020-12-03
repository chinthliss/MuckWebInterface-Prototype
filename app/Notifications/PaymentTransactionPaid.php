<?php

namespace App\Notifications;

use App\Payment\PaymentTransaction;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;

class PaymentTransactionPaid extends Notification
{
    use Queueable;

    /**
     * @var string
     */
    public $purchaseDescription;

    /**
     * @var float
     */
    public $totalAmountUsd;

    /**
     * @var string
     */
    public $paymentMethod;

    /**
     * @var string
     */
    public $transactionId;

    /**
     * @var string|null
     */
    public $subscriptionId;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(PaymentTransaction $transaction)
    {
        $this->purchaseDescription = $transaction->purchaseDescription;
        $this->totalAmountUsd = $transaction->totalPriceUsd();
        $this->paymentMethod = $transaction->type();
        $this->transactionId = $transaction->id;
        $this->subscriptionId = $transaction->subscriptionId;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    public function transactionUrl()
    {
        return route('accountcurrency.transaction', ['id' => $this->transactionId]);
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $mail = new MailMessage;
        $mail->subject('Receipt of Payment for ' . config('app.name'))
            ->greeting('Payment Receipt')
            ->line('A ' . $this->paymentMethod . ' payment for $'
                . round($this->totalAmountUsd, 2) . '(USD) has been made for the following:')
            ->line($this->purchaseDescription)
            ->action('View Further Details', $this->transactionUrl());

        if ($this->subscriptionId) {
            $mail->line("This payment was made as part of your subscription.");
        }

        $mail->line("Thank you for supporting " . config('app.name') . ".");

        return $mail;
    }

    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
