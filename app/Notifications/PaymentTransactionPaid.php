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

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $mail = new MailMessage;
        $mail->subject('Receipt of Payment for Flexible Survival')
            ->line('The following ' . $this->paymentMethod . ' payment has been made:')
            ->line('$' . round($this->totalAmountUsd, 2) . ' for:')
            ->line($this->purchaseDescription)
            ->line("Transaction ID: " . $this->transactionId);

        if ($this->subscriptionId) {
            $mail->line("This payment was made as part of your subscription.");
        }

        $mail->line('It may take a few moments for this transaction to appear on your account.');
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
