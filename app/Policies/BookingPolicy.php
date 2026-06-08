<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    /**
     * Admin bisa mengakses semua ability booking.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return (bool) $user;
    }

    public function view(User $user, Booking $booking): bool
    {
        if ($booking->requester_id === $user->id) {
            return true;
        }

        return (bool) $booking->current_role_key && $user->hasRole($booking->current_role_key);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('ketua_pelaksana');
    }

    public function update(User $user, Booking $booking): bool
    {
        if (!in_array($booking->status, ['draft', 'revision_requested'], true)) {
            return false;
        }

        return $booking->requester_id === $user->id;
    }

    public function resubmit(User $user, Booking $booking): bool
    {
        return $booking->status === 'revision_requested'
            && $booking->requester_id === $user->id;
    }

    public function manageAttachments(User $user, Booking $booking): bool
    {
        if ($booking->requester_id !== $user->id) {
            return false;
        }

        return !in_array($booking->status, ['approved_final', 'rejected'], true);
    }

    public function processAction(User $user, Booking $booking): bool
    {
        return (bool) $booking->current_role_key
            && $user->hasRole($booking->current_role_key);
    }

    public function viewApprovalQueue(User $user): bool
    {
        return $user->hasAnyRole($this->approverRoleKeys());
    }

    public function exportApprovalQueue(User $user): bool
    {
        return $user->hasAnyRole($this->approverRoleKeys());
    }

    public function viewKasubbagQueue(User $user): bool
    {
        return $user->hasRole('kasubbag');
    }

    public function exportKasubbagQueue(User $user): bool
    {
        return $user->hasRole('kasubbag');
    }

    public function reviewKasubbag(User $user, Booking $booking): bool
    {
        return $booking->current_role_key === 'kasubbag'
            && $user->hasRole('kasubbag');
    }

    /**
     * @return array<int, string>
     */
    private function approverRoleKeys(): array
    {
        return [
            'ketua_ormawa',
            'ketua_ukm',
            'kaprodi',
            'pembina_ukm',
            'wadir3',
            'kasubbag',
        ];
    }
}
