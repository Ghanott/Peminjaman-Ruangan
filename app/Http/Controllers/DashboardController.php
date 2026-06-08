<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        $roleNames = $user->getRoleNames()->values();
        $approverRoles = $this->approverRoleKeys();
        $today = now()->toDateString();
        $nextWeek = now()->addDays(7)->toDateString();

        $myBookingsBase = Booking::query()
            ->where('requester_id', $user->id);

        $myStatusCounts = [
            'total' => (clone $myBookingsBase)->count(),
            'draft' => (clone $myBookingsBase)->where('status', 'draft')->count(),
            'revision' => (clone $myBookingsBase)->where('status', 'revision_requested')->count(),
            'in_progress' => (clone $myBookingsBase)
                ->whereIn('status', [
                    'submitted',
                    'waiting_ketua_org',
                    'waiting_kaprodi',
                    'waiting_pembina_ukm',
                    'waiting_wadir3',
                    'waiting_kasubbag_verification',
                    'waiting_kasubbag_signature',
                ])
                ->count(),
            'approved' => (clone $myBookingsBase)->where('status', 'approved_final')->count(),
            'rejected' => (clone $myBookingsBase)->where('status', 'rejected')->count(),
        ];

        $myUpcomingCount = (clone $myBookingsBase)
            ->whereDate('event_date', '>=', $today)
            ->whereDate('event_date', '<=', $nextWeek)
            ->whereNotIn('status', ['rejected'])
            ->count();

        $myUpcomingBookings = (clone $myBookingsBase)
            ->with(['organization:id,name', 'room:id,name,code'])
            ->whereDate('event_date', '>=', $today)
            ->whereNotIn('status', ['rejected'])
            ->orderBy('event_date')
            ->orderBy('start_time')
            ->limit(6)
            ->get([
                'id',
                'organization_id',
                'room_id',
                'event_name',
                'event_date',
                'start_time',
                'end_time',
                'status',
            ]);

        $approverRoleNames = $roleNames
            ->filter(fn ($role) => in_array($role, $approverRoles, true))
            ->values();

        $pendingByRole = $approverRoleNames
            ->mapWithKeys(function (string $role) {
                return [
                    $role => Booking::query()
                        ->where('current_role_key', $role)
                        ->count(),
                ];
            });

        $pendingApprovalCount = $pendingByRole->sum();

        $urgentApprovalCount = Booking::query()
            ->when(
                $approverRoleNames->isNotEmpty(),
                fn ($query) => $query->whereIn('current_role_key', $approverRoleNames->all()),
                fn ($query) => $query->whereRaw('1 = 0')
            )
            ->whereDate('event_date', '<=', now()->addDay()->toDateString())
            ->count();

        $todayUsageCount = Booking::query()
            ->whereDate('event_date', $today)
            ->whereIn('status', ['approved_final', 'waiting_kasubbag_verification', 'waiting_wadir3', 'waiting_kaprodi', 'waiting_pembina_ukm', 'waiting_ketua_org'])
            ->count();

        $finalizedThisMonthCount = Booking::query()
            ->where('status', 'approved_final')
            ->whereYear('finalized_at', now()->year)
            ->whereMonth('finalized_at', now()->month)
            ->count();

        return view('dashboard', [
            'roles' => $roleNames,
            'roleLabels' => $this->roleLabelMap(),
            'myStatusCounts' => $myStatusCounts,
            'myUpcomingCount' => $myUpcomingCount,
            'myUpcomingBookings' => $myUpcomingBookings,
            'pendingApprovalCount' => $pendingApprovalCount,
            'pendingByRole' => $pendingByRole,
            'urgentApprovalCount' => $urgentApprovalCount,
            'todayUsageCount' => $todayUsageCount,
            'finalizedThisMonthCount' => $finalizedThisMonthCount,
            'canManageMasterData' => $user->can('manage-master-data'),
            'canOpenApprovalQueue' => $user->hasRole('admin') || $user->hasAnyRole($approverRoles),
            'canOpenKasubbagQueue' => $user->hasRole('admin') || $user->hasRole('kasubbag'),
            'canCreateBooking' => $user->hasRole('admin') || $user->hasRole('ketua_pelaksana'),
        ]);
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

    /**
     * @return array<string, string>
     */
    private function roleLabelMap(): array
    {
        return [
            'admin' => 'Administrator',
            'ketua_pelaksana' => 'Ketua Pelaksana',
            'ketua_ormawa' => 'Ketua Ormawa',
            'ketua_ukm' => 'Ketua UKM',
            'kaprodi' => 'Kaprodi',
            'pembina_ukm' => 'Pembina UKM',
            'wadir3' => 'Wadir 3',
            'kasubbag' => 'Kasubbag',
        ];
    }
}
