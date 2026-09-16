<?php

namespace App\Http\Controllers;

use App\Models\ReferralPayout;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

final class ReferralPayoutExportController extends Controller
{
    public function csv(): Response
    {
        $rows = $this->rows();
        $stream = fopen('php://temp', 'r+');
        fputcsv($stream, ['Период', 'Получатель', 'ИНН', 'Расчётный счёт', 'Банк', 'БИК', 'Корр. счёт', 'Сумма, коп.', 'Статус', 'ID платежа'], ';', '"', '');
        foreach ($rows as $row) {
            fputcsv($stream, $row, ';', '"', '');
        }
        rewind($stream);
        $content = stream_get_contents($stream);
        fclose($stream);

        return response("\xEF\xBB\xBF".$content, 200, ['Content-Type' => 'text/csv; charset=UTF-8', 'Content-Disposition' => 'attachment; filename="referral-payouts.csv"']);
    }

    public function xlsx(): BinaryFileResponse
    {
        abort_unless(class_exists(ZipArchive::class), 503, 'Расширение ZIP недоступно.');
        $file = tempnam(sys_get_temp_dir(), 'referral-payouts-');
        $zip = new ZipArchive;
        abort_unless($zip->open($file, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true, 500, 'Не удалось создать XLSX.');
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Выплаты" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>');
        $rows = array_merge([['Период', 'Получатель', 'ИНН', 'Расчётный счёт', 'Банк', 'БИК', 'Корр. счёт', 'Сумма, коп.', 'Статус', 'ID платежа']], $this->rows());
        $xmlRows = '';
        foreach ($rows as $index => $row) {
            $cells = '';
            foreach ($row as $value) {
                $cells .= '<c t="inlineStr"><is><t>'.htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8').'</t></is></c>';
            }
            $xmlRows .= '<row r="'.($index + 1).'">'.$cells.'</row>';
        }
        $zip->addFromString('xl/worksheets/sheet1.xml', '<?xml version="1.0" encoding="UTF-8"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>'.$xmlRows.'</sheetData></worksheet>');
        $zip->close();

        return response()->download($file, 'referral-payouts.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])->deleteFileAfterSend();
    }

    /** @return array<int, array<int, int|string>> */
    private function rows(): array
    {
        return ReferralPayout::query()->with('participant')->latest('period_end')->get()->map(function (ReferralPayout $payout): array {
            $details = (array) $payout->participant->bank_details;

            return [
                $payout->period_start->format('d.m.Y').'–'.$payout->period_end->format('d.m.Y'),
                $details['recipient'] ?? $payout->participant->company_name,
                $payout->participant->inn,
                $details['account'] ?? '',
                $details['bank_name'] ?? '',
                $details['bik'] ?? '',
                $details['correspondent_account'] ?? '',
                $payout->amount_minor,
                $payout->status,
                $payout->payment_reference ?? '',
            ];
        })->all();
    }
}
