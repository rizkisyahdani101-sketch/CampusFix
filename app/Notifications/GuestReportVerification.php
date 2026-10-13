<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class GuestReportVerification extends Notification
{
    public function __construct(public string $verificationUrl)
    {
    }

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Konfirmasi email laporan CampusFix UNIMED')
            ->greeting('Konfirmasi alamat email')
            ->line('Klik tombol berikut untuk mengonfirmasi email dan mengirim laporan fasilitas Universitas Negeri Medan (UNIMED).')
            ->action('Konfirmasi dan kirim laporan', $this->verificationUrl)
            ->line('Tautan ini berlaku selama 24 jam. Laporan baru diteruskan ke antrean koordinator setelah email dikonfirmasi.');
    }
}
