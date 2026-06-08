<?php

namespace App\View\Components;

use App\Models\Booking;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;
use Illuminate\View\View;

class AppLayout extends Component
{
    /**
     * Get the view / contents that represents the component.
     */
    public function render(): View
    {
        $queueBadges = [
            'approval' => [
                'total' => 0,
                'urgent' => 0,
                'overdue' => 0,
            ],
            'kasubbag' => [
                'total' => 0,
                'urgent' => 0,
                'overdue' => 0,
            ],
        ];

        $user = Auth::user();

        if ($user) {
            $today = Carbon::today()->toDateString();
            $tomorrow = Carbon::tomorrow()->toDateString();

            $approverRoleKeys = [
                'ketua_ormawa',
                'ketua_ukm',
                'kaprodi',
                'pembina_ukm',
                'wadir3',
                'kasubbag',
            ];

            $isAdmin = $user->hasRole('admin');
            $userRoles = $user->getRoleNames()->values()->all();
            $visibleApproverRoles = $isAdmin
                ? $approverRoleKeys
                : array_values(array_intersect($userRoles, $approverRoleKeys));

            if (!empty($visibleApproverRoles)) {
                $approvalBaseQuery = Booking::query()
                    ->whereIn('current_role_key', $visibleApproverRoles);

                $queueBadges['approval']['total'] = (clone $approvalBaseQuery)->count();
                $queueBadges['approval']['urgent'] = (clone $approvalBaseQuery)
                    ->whereBetween('event_date', [$today, $tomorrow])
                    ->count();
                $queueBadges['approval']['overdue'] = (clone $approvalBaseQuery)
                    ->whereDate('event_date', '<', $today)
                    ->count();
            }

            if ($isAdmin || $user->hasRole('kasubbag')) {
                $kasubbagBaseQuery = Booking::query()
                    ->where('current_role_key', 'kasubbag')
                    ->whereIn('status', ['waiting_kasubbag_verification', 'waiting_kasubbag_signature']);

                $queueBadges['kasubbag']['total'] = (clone $kasubbagBaseQuery)->count();
                $queueBadges['kasubbag']['urgent'] = (clone $kasubbagBaseQuery)
                    ->whereBetween('event_date', [$today, $tomorrow])
                    ->count();
                $queueBadges['kasubbag']['overdue'] = (clone $kasubbagBaseQuery)
                    ->whereDate('event_date', '<', $today)
                    ->count();
            }
        }

        return view('layouts.app', [
            'queueBadges' => $queueBadges,
        ]);
    }
}
