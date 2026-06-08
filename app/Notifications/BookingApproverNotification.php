<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingApproverNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Booking $booking,
        private readonly string $targetRoleKey,
        private readonly string $reason,
        private readonly ?string $actorName = null,
        private readonly ?string $note = null,
    ) {
    }

    /**
     * @param object $notifiable
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subject = match ($this->reason) {
            'submitted' => 'Pengajuan baru menunggu persetujuan',
            'resubmitted' => 'Revisi pengajuan sudah dikirim ulang',
            default => 'Pengajuan menunggu persetujuan Anda',
        };

        $roleLabel = $this->roleLabel($this->targetRoleKey);
        $url = $this->targetRoleKey === 'kasubbag'
            ? route('bookings.kasubbag-review', $this->booking)
            : route('bookings.show', $this->booking);

        $mail = (new MailMessage())
            ->subject($subject)
            ->greeting('Halo '.$notifiable->name.',')
            ->line('Ada pengajuan peminjaman ruangan yang perlu Anda proses.')
            ->line('Kegiatan: '.$this->booking->event_name)
            ->line('Organisasi: '.($this->booking->organization?->name ?? '-'))
            ->line('Tanggal: '.$this->booking->event_date?->format('d-m-Y'))
            ->line('Jam: '.substr((string) $this->booking->start_time, 0, 5).' - '.substr((string) $this->booking->end_time, 0, 5))
            ->line('Tahap untuk role: '.$roleLabel);

        if ($this->actorName) {
            $mail->line('Diproses sebelumnya oleh: '.$this->actorName);
        }

        if ($this->reason === 'resubmitted' && filled($this->note)) {
            $mail->line('Catatan resubmit: '.$this->note);
        }

        return $mail
            ->action('Buka Pengajuan', $url)
            ->line('Silakan login ke aplikasi untuk menindaklanjuti.');
    }

    private function roleLabel(string $roleKey): string
    {
        return match ($roleKey) {
            'ketua_ormawa' => 'Ketua ORMAWA',
            'ketua_ukm' => 'Ketua UKM',
            'kaprodi' => 'Kaprodi',
            'pembina_ukm' => 'Pembina UKM',
            'wadir3' => 'Wadir 3',
            'kasubbag' => 'Kasubbag',
            default => $roleKey,
        };
    }
}
