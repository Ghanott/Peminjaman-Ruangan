<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingRequesterNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Booking $booking,
        private readonly string $event,
        private readonly ?string $actorName = null,
        private readonly ?string $note = null,
        private readonly ?string $nextRoleKey = null,
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
        $subject = match ($this->event) {
            'approved_final' => 'Pengajuan disetujui final',
            'approve_step' => 'Pengajuan lanjut ke tahap berikutnya',
            'rejected' => 'Pengajuan ditolak',
            'revision_requested' => 'Pengajuan perlu revisi',
            default => 'Update status pengajuan',
        };

        $mail = (new MailMessage())
            ->subject($subject)
            ->greeting('Halo '.$notifiable->name.',')
            ->line('Status pengajuan Anda telah diperbarui.')
            ->line('Kegiatan: '.$this->booking->event_name)
            ->line('Organisasi: '.($this->booking->organization?->name ?? '-'))
            ->line('Ruangan: '.($this->booking->room?->name ?? '-'))
            ->line('Tanggal: '.$this->booking->event_date?->format('d-m-Y'))
            ->line('Jam: '.substr((string) $this->booking->start_time, 0, 5).' - '.substr((string) $this->booking->end_time, 0, 5))
            ->line('Status sekarang: '.$this->booking->status);

        if ($this->actorName) {
            $mail->line('Diproses oleh: '.$this->actorName);
        }

        if ($this->event === 'approve_step' && $this->nextRoleKey) {
            $mail->line('Tahap berikutnya: '.$this->roleLabel($this->nextRoleKey));
        }

        if (filled($this->note)) {
            $mail->line('Catatan: '.$this->note);
        }

        return $mail
            ->action('Lihat Detail Pengajuan', route('bookings.show', $this->booking))
            ->line('Terima kasih.');
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
