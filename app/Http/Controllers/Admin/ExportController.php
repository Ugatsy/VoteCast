<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VotingSession;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;

class ExportController extends Controller
{
    // ── Shared: load session with votes + student in one constraint ───────────

    private function loadSession(VotingSession $session): void
    {
        $session->load([
            'positions',
            'positions.candidates' => function ($q) {
                // Load votes_count AND student in one go to avoid constraint conflict
                $q->withCount('votes')
                  ->with('student')
                  ->orderByDesc('votes_count');
            },
        ]);
    }

    // ── Shared: resolve scope label & value ───────────────────────────────────

    private function getScopeInfo(VotingSession $session): array
    {
        return match($session->category) {
            'course'     => ['label' => 'Target Course',     'value' => $session->target_course     ?? 'N/A'],
            'section'    => ['label' => 'Target Section',    'value' => $session->target_section    ?? 'N/A'],
            'department' => ['label' => 'Target Department', 'value' => $session->target_department ?: 'All Students (Department-wide)'],
            'manual'     => ['label' => 'Voter Selection',   'value' => 'Manual Voter List'],
            default      => ['label' => 'Scope',             'value' => 'N/A'],
        };
    }

    // ── Excel Export ──────────────────────────────────────────────────────────

    public function exportExcel(VotingSession $votingSession)
    {
        abort_unless(
            in_array($votingSession->status, ['completed', 'active']),
            403,
            'Results are only available for active or completed elections.'
        );

        $this->loadSession($votingSession);

        $totalVoters = $votingSession->total_voters;
        $totalVoted  = $votingSession->total_votes_cast;
        $turnout     = $totalVoters > 0 ? round(($totalVoted / $totalVoters) * 100, 2) : 0;
        $scope       = $this->getScopeInfo($votingSession);

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setTitle($votingSession->title . ' - Election Results')
            ->setCreator('VoteCast System');

        // ── Reusable styles ───────────────────────────────────────────────────
        $headerStyle = [
            'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 12],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1A56DB']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ];

        $colHeaderStyle = [
            'font' => ['bold' => true, 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE2E8F0']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFCBD5E1']]],
        ];

        $scopeRowStyle = [
            'font' => ['bold' => true, 'size' => 10, 'color' => ['argb' => 'FF1E40AF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFDBEAFE']],
        ];

        $winnerStyle = [
            'font' => ['bold' => true, 'color' => ['argb' => 'FF15803D']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFDCFCE7']],
        ];

        $altRowColor = 'FFF8FAFC';

        // ── Sheet 1: Summary ─────────────────────────────────────────────────
        $summary = $spreadsheet->getActiveSheet();
        $summary->setTitle('Summary');

        $summary->mergeCells('A1:E1');
        $summary->setCellValue('A1', strtoupper($votingSession->title) . ' - ELECTION RESULTS');
        $summary->getStyle('A1')->applyFromArray($headerStyle);
        $summary->getRowDimension(1)->setRowHeight(28);

        $summaryData = [
            3  => ['Election Category', ucfirst($votingSession->category) . ' Election'],
            4  => [$scope['label'],     $scope['value']],
            5  => ['Status',            ucfirst($votingSession->status)],
            6  => ['Period',            $votingSession->start_date->format('M d, Y H:i') . ' to ' . $votingSession->end_date->format('M d, Y H:i')],
            7  => ['Eligible Voters',   $totalVoters],
            8  => ['Votes Cast',        $totalVoted],
            9  => ['Voter Turnout',     $turnout . '%'],
            10 => ['Generated On',      now()->format('M d, Y H:i')],
        ];

        foreach ($summaryData as $row => [$label, $value]) {
            $summary->setCellValue("A{$row}", $label);
            $summary->setCellValue("B{$row}", $value);
            $summary->getStyle("A{$row}")->getFont()->setBold(true);
        }

        // Scope row highlighted
        $summary->getStyle('A4:B4')->applyFromArray($scopeRowStyle);

        $summary->getColumnDimension('A')->setWidth(24);
        $summary->getColumnDimension('B')->setWidth(44);

        // ── Sheets per Position ───────────────────────────────────────────────
        foreach ($votingSession->positions as $position) {
            $sheet = $spreadsheet->createSheet();
            $sheet->setTitle(mb_substr(preg_replace('/[\/\\\?\*\[\]:]/', '', $position->title), 0, 31));

            // votes_count is now correctly loaded
            $positionTotalVotes = $position->candidates->sum('votes_count');

            // Title row
            $sheet->mergeCells('A1:F1');
            $sheet->setCellValue('A1', strtoupper($position->title));
            $sheet->getStyle('A1')->applyFromArray($headerStyle);
            $sheet->getRowDimension(1)->setRowHeight(24);

            // Scope sub-info row
            $sheet->mergeCells('A2:F2');
            $sheet->setCellValue('A2',
                ucfirst($votingSession->category) . ' Election'
                . '   |   ' . $scope['label'] . ': ' . $scope['value']
                . '   |   Total Votes: ' . number_format($positionTotalVotes)
                . '   |   Max Winners: ' . $position->max_winners
            );
            $sheet->getStyle('A2')->applyFromArray([
                'font' => ['italic' => true, 'size' => 9, 'color' => ['argb' => 'FF1E40AF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFDBEAFE']],
            ]);

            // Column headers row 4
            foreach (['Rank', 'Candidate Name', 'Section', 'Votes', 'Percentage', 'Status'] as $i => $col) {
                $sheet->setCellValue(chr(65 + $i) . '4', $col);
            }
            $sheet->getStyle('A4:F4')->applyFromArray($colHeaderStyle);

            // Data rows — candidates already sorted by votes_count desc from the load
            $rank = 1;
            foreach ($position->candidates as $candidate) {
                $dataRow    = 4 + $rank;
                $pct        = $positionTotalVotes > 0 ? round($candidate->votes_count / $positionTotalVotes * 100, 2) : 0;
                $isWinner   = $rank <= $position->max_winners;

                $sheet->setCellValue("A{$dataRow}", $rank);
                $sheet->setCellValue("B{$dataRow}", $candidate->student->full_name ?? '-');
                $sheet->setCellValue("C{$dataRow}", $candidate->student->section   ?? '-');
                $sheet->setCellValue("D{$dataRow}", $candidate->votes_count);
                $sheet->setCellValue("E{$dataRow}", $pct . '%');
                $sheet->setCellValue("F{$dataRow}", $isWinner ? 'Winner' : '');

                if ($isWinner) {
                    $sheet->getStyle("A{$dataRow}:F{$dataRow}")->applyFromArray($winnerStyle);
                } elseif ($rank % 2 === 0) {
                    $sheet->getStyle("A{$dataRow}:F{$dataRow}")->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setARGB($altRowColor);
                }

                $rank++;
            }

            $lastRow = 4 + $position->candidates->count();
            $sheet->getStyle("A4:F{$lastRow}")->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFE2E8F0']]],
            ]);

            $sheet->getColumnDimension('A')->setWidth(8);
            $sheet->getColumnDimension('B')->setWidth(32);
            $sheet->getColumnDimension('C')->setWidth(16);
            $sheet->getColumnDimension('D')->setWidth(12);
            $sheet->getColumnDimension('E')->setWidth(14);
            $sheet->getColumnDimension('F')->setWidth(14);
        }

        // ── Sheet: All Results (flat) ─────────────────────────────────────────
        $all = $spreadsheet->createSheet();
        $all->setTitle('All Results');

        $all->mergeCells('A1:G1');
        $all->setCellValue('A1', $votingSession->title . ' - Full Results');
        $all->getStyle('A1')->applyFromArray($headerStyle);
        $all->getRowDimension(1)->setRowHeight(24);

        $all->mergeCells('A2:G2');
        $all->setCellValue('A2', ucfirst($votingSession->category) . ' Election   |   ' . $scope['label'] . ': ' . $scope['value']);
        $all->getStyle('A2')->applyFromArray($scopeRowStyle);
        $all->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        foreach (['Position', 'Rank', 'Candidate', 'Section', 'Votes', 'Percentage', 'Status'] as $i => $col) {
            $all->setCellValue(chr(65 + $i) . '4', $col);
        }
        $all->getStyle('A4:G4')->applyFromArray($colHeaderStyle);

        $allRow = 5;
        foreach ($votingSession->positions as $position) {
            $posTotal = $position->candidates->sum('votes_count');
            $rank     = 1;

            // candidates already sorted desc by votes_count
            foreach ($position->candidates as $candidate) {
                $pct      = $posTotal > 0 ? round($candidate->votes_count / $posTotal * 100, 2) : 0;
                $isWinner = $rank <= $position->max_winners;

                $all->setCellValue("A{$allRow}", $position->title);
                $all->setCellValue("B{$allRow}", $rank);
                $all->setCellValue("C{$allRow}", $candidate->student->full_name ?? '-');
                $all->setCellValue("D{$allRow}", $candidate->student->section   ?? '-');
                $all->setCellValue("E{$allRow}", $candidate->votes_count);
                $all->setCellValue("F{$allRow}", $pct . '%');
                $all->setCellValue("G{$allRow}", $isWinner ? 'Winner' : '');

                if ($isWinner) {
                    $all->getStyle("A{$allRow}:G{$allRow}")->applyFromArray($winnerStyle);
                } elseif ($rank % 2 === 0) {
                    $all->getStyle("A{$allRow}:G{$allRow}")->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setARGB($altRowColor);
                }

                $rank++;
                $allRow++;
            }
        }

        $all->getStyle("A4:G" . ($allRow - 1))->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFE2E8F0']]],
        ]);

        foreach (['A' => 28, 'B' => 8, 'C' => 32, 'D' => 16, 'E' => 12, 'F' => 14, 'G' => 12] as $col => $width) {
            $all->getColumnDimension($col)->setWidth($width);
        }

        $spreadsheet->setActiveSheetIndex(0);

        $filename = 'election-results-' . str($votingSession->title)->slug() . '-' . now()->format('Ymd') . '.xlsx';
        $writer   = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    // ── DOCX Export ───────────────────────────────────────────────────────────

    public function exportDocx(VotingSession $votingSession)
    {
        abort_unless(
            in_array($votingSession->status, ['completed', 'active']),
            403,
            'Results are only available for active or completed elections.'
        );

        $this->loadSession($votingSession);

        $totalVoters = $votingSession->total_voters;
        $totalVoted  = $votingSession->total_votes_cast;
        $turnout     = $totalVoters > 0 ? round(($totalVoted / $totalVoters) * 100, 2) : 0;
        $scope       = $this->getScopeInfo($votingSession);

        $phpWord = new PhpWord();
        $phpWord->getSettings()->setThemeFontLang(new \PhpOffice\PhpWord\Style\Language('en-US'));

        $phpWord->addTitleStyle(1, ['bold' => true, 'size' => 18, 'color' => '1A56DB', 'name' => 'Arial']);
        $phpWord->addTitleStyle(2, ['bold' => true, 'size' => 14, 'color' => '1E40AF', 'name' => 'Arial']);

        $phpWord->addFontStyle('labelFont',    ['bold' => true, 'size' => 10, 'name' => 'Arial']);
        $phpWord->addFontStyle('valueFont',    ['size' => 10, 'name' => 'Arial']);
        $phpWord->addFontStyle('scopeLabel',   ['bold' => true, 'size' => 10, 'color' => '1E40AF', 'name' => 'Arial']);
        $phpWord->addFontStyle('scopeValue',   ['bold' => true, 'size' => 10, 'color' => '1E40AF', 'name' => 'Arial']);
        $phpWord->addFontStyle('winnerFont',   ['bold' => true, 'size' => 10, 'color' => '15803D', 'name' => 'Arial']);
        $phpWord->addFontStyle('positionFont', ['bold' => true, 'size' => 13, 'color' => '1E40AF', 'name' => 'Arial']);
        $phpWord->addFontStyle('smallGray',    ['size' => 9, 'color' => '64748B', 'name' => 'Arial']);
        $phpWord->addFontStyle('tableHeader',  ['bold' => true, 'size' => 10, 'color' => 'FFFFFF', 'name' => 'Arial']);

        $phpWord->addParagraphStyle('centered', ['alignment' => Jc::CENTER]);
        $phpWord->addParagraphStyle('spaced',   ['spaceAfter' => 120]);

        $section = $phpWord->addSection([
            'marginTop' => 1080, 'marginBottom' => 1080,
            'marginLeft' => 1080, 'marginRight' => 1080,
        ]);

        $section->addTitle(strtoupper($votingSession->title), 1);
        $section->addTitle('Official Election Results', 2);
        $section->addTextBreak(1);

        // Summary table
        $summaryTable = $section->addTable(['borderSize' => 0, 'cellMargin' => 100]);

        $summaryRows = [
            ['Election Category', ucfirst($votingSession->category) . ' Election', 'labelFont', 'valueFont', 'E2E8F0', 'FFFFFF'],
            [$scope['label'],     $scope['value'],                                  'scopeLabel', 'scopeValue', 'DBEAFE', 'DBEAFE'],
            ['Status',            ucfirst($votingSession->status),                  'labelFont', 'valueFont', 'E2E8F0', 'FFFFFF'],
            ['Period',            $votingSession->start_date->format('M d, Y H:i') . ' - ' . $votingSession->end_date->format('M d, Y H:i'), 'labelFont', 'valueFont', 'E2E8F0', 'FFFFFF'],
            ['Eligible Voters',   number_format($totalVoters),                      'labelFont', 'valueFont', 'E2E8F0', 'FFFFFF'],
            ['Votes Cast',        number_format($totalVoted),                       'labelFont', 'valueFont', 'E2E8F0', 'FFFFFF'],
            ['Voter Turnout',     $turnout . '%',                                   'labelFont', 'valueFont', 'E2E8F0', 'FFFFFF'],
            ['Generated On',      now()->format('F d, Y - H:i'),                   'labelFont', 'valueFont', 'E2E8F0', 'FFFFFF'],
        ];

        foreach ($summaryRows as [$label, $value, $lf, $vf, $lbg, $vbg]) {
            $row = $summaryTable->addRow();
            $row->addCell(2000, ['bgColor' => $lbg])->addText($label, $lf);
            $row->addCell(6000, ['bgColor' => $vbg])->addText($value, $vf);
        }

        $section->addTextBreak(2);

        foreach ($votingSession->positions as $position) {
            $positionTotalVotes = $position->candidates->sum('votes_count');

            $section->addText($position->title, 'positionFont');
            $section->addText(
                ucfirst($votingSession->category) . ' Election'
                . '  |  ' . $scope['label'] . ': ' . $scope['value']
                . '  |  Total votes: ' . number_format($positionTotalVotes)
                . '  |  Max winners: ' . $position->max_winners,
                'smallGray', 'spaced'
            );

            $resultsTable = $section->addTable(['borderSize' => 6, 'borderColor' => 'E2E8F0', 'cellMargin' => 100]);

            $headerRow = $resultsTable->addRow(400);
            foreach ([[500, 'Rank'], [2800, 'Candidate Name'], [1200, 'Section'], [900, 'Votes'], [1100, '%'], [1000, 'Status']] as [$width, $text]) {
                $headerRow->addCell($width, ['bgColor' => '1A56DB', 'valign' => 'center'])
                          ->addText($text, 'tableHeader', 'centered');
            }

            $rank = 1;
            // candidates already sorted desc by votes_count from loadSession()
            foreach ($position->candidates as $candidate) {
                $pct      = $positionTotalVotes > 0 ? round($candidate->votes_count / $positionTotalVotes * 100, 1) : 0;
                $isWinner = $rank <= $position->max_winners;
                $rowBg    = $isWinner ? 'DCFCE7' : ($rank % 2 === 0 ? 'F8FAFC' : 'FFFFFF');
                $font     = $isWinner ? 'winnerFont' : 'valueFont';

                $dataRow = $resultsTable->addRow();
                $dataRow->addCell(500,  ['bgColor' => $rowBg])->addText((string) $rank, $font, 'centered');
                $dataRow->addCell(2800, ['bgColor' => $rowBg])->addText($candidate->student->full_name ?? '-', $font);
                $dataRow->addCell(1200, ['bgColor' => $rowBg])->addText($candidate->student->section   ?? '-', $font);
                $dataRow->addCell(900,  ['bgColor' => $rowBg])->addText(number_format($candidate->votes_count), $font, 'centered');
                $dataRow->addCell(1100, ['bgColor' => $rowBg])->addText($pct . '%', $font, 'centered');
                $dataRow->addCell(1000, ['bgColor' => $rowBg])->addText($isWinner ? 'Winner' : '', $font, 'centered');

                $rank++;
            }

            $section->addTextBreak(2);
        }

        $section->addText(
            'This document was generated automatically by VoteCast. Results are official as of ' . now()->format('F d, Y H:i') . '.',
            'smallGray', 'centered'
        );

        $filename = 'election-results-' . str($votingSession->title)->slug() . '-' . now()->format('Ymd') . '.docx';
        $writer   = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
