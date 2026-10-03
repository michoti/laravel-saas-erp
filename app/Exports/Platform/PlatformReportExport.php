<?php

declare(strict_types=1);

namespace App\Exports\Platform;

use App\Reports\Platform\PlatformReportResult;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Generic writer for any platform report. It carries no query logic of its
 * own — it simply serialises a pre-built PlatformReportResult, so a single
 * export class backs all ten report pages for both the .xlsx and .csv
 * downloads.
 */
final class PlatformReportExport implements FromCollection, ShouldAutoSize, WithHeadings, WithTitle
{
    public function __construct(private readonly PlatformReportResult $report) {}

    /**
     * @return Collection<int, list<string|int|float>>
     */
    public function collection(): Collection
    {
        return $this->report->rows;
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return $this->report->headings;
    }

    public function title(): string
    {
        return $this->report->title;
    }
}
