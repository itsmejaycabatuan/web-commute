<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class UserDataExport implements FromArray, WithColumnWidths, WithEvents
{
    use Exportable;

    public function __construct(public User $user) {}

    public function columnWidths(): array
    {
        return [
            'A' => 25,
            'B' => 30,
            'C' => 30,
            'D' => 15,
            'E' => 15,
            'F' => 22,
            'G' => 15,
            'H' => 25,
        ];
    }

    // ✅ DYNAMIC STYLING: Scans the sheet and bolds uppercase headers automatically
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();

                for ($row = 1; $row <= $highestRow; $row++) {
                    $cellValue = $sheet->getCell("A{$row}")->getValue();

                    // If the cell is ALL UPPERCASE and long, it's a section header
                    if ($cellValue && strtoupper($cellValue) === $cellValue && strlen($cellValue) > 5) {
                        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(14);
                    }

                    // Make the row immediately after a section header bold (the table column names)
                    $prevValue = $row > 1 ? $sheet->getCell('A' . ($row - 1))->getValue() : null;
                    if ($prevValue && strtoupper($prevValue) === $prevValue && strlen($prevValue) > 5 && $cellValue && strtoupper($cellValue) !== $cellValue) {
                        $sheet->getStyle("A{$row}:H{$row}")->getFont()->setBold(true);
                    }
                }
            },
        ];
    }

    public function array(): array
    {
        $data = [];

        // ── SECTION 1: Account Info ──
        $data[] = ['ACCOUNT INFORMATION'];
        $data[] = ['Email', $this->user->email ?? 'N/A'];
        $data[] = ['Role', $this->user->roles->first()->name ?? 'user'];
        $data[] = ['Member Since', $this->user->created_at?->format('M d, Y g:i A') ?? 'N/A'];
        $data[] = ['Email Verified', $this->user->email_verified_at ? 'Yes' : 'No'];
        $data[] = ['Wallet Balance', $this->user->wallet ? number_format($this->user->wallet->balance, 2) : '0.00'];

        $data[] = []; // Empty row spacer

        // ── SECTION 2: Trip History ──
        $data[] = ['TRIP & PAYMENT HISTORY'];
        $data[] = ['Transaction ID', 'Starting Point', 'Destination', 'Distance', 'Discounted?', 'Payment Method', 'Price', 'Paid At'];

        foreach ($this->user->payment()->latest()->get() as $trip) {
            $data[] = [
                $trip->transaction_id,
                $trip->starting_point,
                $trip->destination,
                $trip->total_distance,
                $trip->is_discounted ? 'Yes' : 'No',
                $trip->payment_method,
                number_format($trip->price, 2),
                $trip->paid_at?->format('M d, Y g:i A') ?? 'N/A', // Safe from null dates
            ];
        }

        $data[] = []; // Empty row spacer

        // ── SECTION 3: Top-Up History ──
        $data[] = ['WALLET TOP-UP HISTORY'];
        $data[] = ['Amount Added', 'Payment Method', 'Date'];

        foreach ($this->user->topupHistories()->latest()->get() as $topup) {
            $data[] = [
                number_format($topup->amount_added, 2),
                $topup->payment_method,
                $topup->created_at?->format('M d, Y g:i A') ?? 'N/A',
            ];
        }

        return $data;
    }
}
