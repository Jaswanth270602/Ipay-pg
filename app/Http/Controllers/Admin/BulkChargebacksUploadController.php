<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Traits\LogsConditionally;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Storage;

class BulkChargebacksUploadController extends Controller
{
    use LogsConditionally;

    public function index(): View
    {
        $this->logInfo('Admin bulk chargebacks upload page accessed', ['user_id' => auth()->id()]);
        return view('admin.payments.bulk-chargebacks-upload');
    }

    public function upload(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'file' => 'required|file|mimes:csv,xlsx,xls|max:10240',
            ]);

            $file = $request->file('file');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('bulk_chargebacks', $fileName);

            return response()->json([
                'success' => true,
                'message' => 'File uploaded successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload file',
            ], 500);
        }
    }

    public function downloadTemplate(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="bulk_chargeback_template.csv"',
        ];

        $callback = function() {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Transaction ID', 'Chargeback Amount', 'Reason']);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}

