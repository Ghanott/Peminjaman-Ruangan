<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>SPR {{ $sprDocument->spr_number }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #111827;
            line-height: 1.5;
        }
        h1, h2, p {
            margin: 0;
        }
        .text-center {
            text-align: center;
        }
        .header {
            margin-bottom: 18px;
        }
        .meta {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }
        .meta td {
            padding: 3px 0;
            vertical-align: top;
        }
        .meta td:first-child {
            width: 190px;
        }
        .box {
            border: 1px solid #d1d5db;
            border-radius: 4px;
            padding: 10px;
            margin-top: 8px;
            margin-bottom: 14px;
        }
        .items {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            margin-bottom: 16px;
        }
        .items th,
        .items td {
            border: 1px solid #d1d5db;
            padding: 6px;
            font-size: 11px;
        }
        .items th {
            background: #f3f4f6;
            text-align: left;
        }
        .signature {
            width: 100%;
            margin-top: 24px;
        }
        .signature td {
            width: 50%;
            vertical-align: top;
        }
        .muted {
            color: #6b7280;
            font-size: 11px;
        }
    </style>
</head>
<body>
    <div class="header text-center">
        <h1>SURAT PEMINJAMAN RUANGAN (SPR)</h1>
        <p>Politeknik Negeri Madiun</p>
        <p><strong>Nomor: {{ $sprDocument->spr_number }}</strong></p>
    </div>

    <table class="meta">
        <tr>
            <td>Tanggal Terbit</td>
            <td>: {{ $sprDocument->issued_date->format('d-m-Y') }}</td>
        </tr>
        <tr>
            <td>Organisasi Pengaju</td>
            <td>: {{ $booking->organization->name }}</td>
        </tr>
        <tr>
            <td>Ketua Pelaksana</td>
            <td>: {{ $booking->requester->name }}</td>
        </tr>
        <tr>
            <td>Kegiatan</td>
            <td>: {{ $booking->event_name }}</td>
        </tr>
        <tr>
            <td>Tanggal Pelaksanaan</td>
            <td>: {{ $booking->event_date->format('d-m-Y') }}</td>
        </tr>
        <tr>
            <td>Waktu</td>
            <td>: {{ substr($booking->start_time, 0, 5) }} - {{ substr($booking->end_time, 0, 5) }} WIB</td>
        </tr>
        <tr>
            <td>Ruangan</td>
            <td>: {{ $booking->room->name }} ({{ $booking->room->code }})</td>
        </tr>
        <tr>
            <td>Jumlah Peserta</td>
            <td>: {{ $booking->participant_count ?? '-' }} orang</td>
        </tr>
    </table>

    <div class="box">
        <p><strong>Deskripsi Kegiatan</strong></p>
        <p>{{ $booking->event_description ?: '-' }}</p>
    </div>

    <p><strong>Daftar Alat yang Diajukan</strong></p>
    <table class="items">
        <thead>
            <tr>
                <th>Nama Alat</th>
                <th>Qty Diajukan</th>
                <th>Qty Disetujui</th>
                <th>Status</th>
                <th>Catatan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($booking->bookingItems as $bookingItem)
                <tr>
                    <td>{{ $bookingItem->item?->name ?? '-' }}</td>
                    <td>{{ $bookingItem->requested_qty }}</td>
                    <td>{{ $bookingItem->approved_qty ?? '-' }}</td>
                    <td>{{ $bookingItem->status_item }}</td>
                    <td>{{ $bookingItem->verification_note ?: '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">Tidak ada alat diajukan.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="signature">
        <tr>
            <td></td>
            <td>
                <p>Madiun, {{ $sprDocument->issued_date->format('d-m-Y') }}</p>
                <p>Mengetahui dan Menandatangani,</p>
                <br><br><br>
                <p><strong>{{ $sprDocument->signer?->name ?? '-' }}</strong></p>
                <p class="muted">Kasubbag Sarana dan Prasarana</p>
                @if ($sprDocument->signature_note)
                    <p class="muted">Catatan: {{ $sprDocument->signature_note }}</p>
                @endif
            </td>
        </tr>
    </table>
</body>
</html>
