<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Receipt;
use Illuminate\Support\Facades\DB;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;
use Mike42\Escpos\Printer;

class SendMessageController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        // Convert smart quotes to regular apostrophes
        $message = str_replace(['"', '“', '”', "'", '‘', '’'], ['"', '"', '"', "'", "'", "'"], $request->message);
        $request->merge(['message' => $message]);

        $request->validate([
            'message' => 'required|max:1024|regex:/^[\x00-\x7F\']*$/',
            'transaction' => 'required',
        ]);

        // Use database transaction with locking to prevent race conditions
        $currentYear = now()->year;
        $transactionNumber = DB::transaction(function () use ($currentYear) {
            // Lock the latest receipt for this year to prevent concurrent modifications
            $latestReceipt = Receipt::where('year', $currentYear)->orderBy('transaction_number', 'desc')->lockForUpdate()->first();
            $nextNumber = $latestReceipt ? $latestReceipt->transaction_number + 1 : 1;

            // Create the receipt with the new transaction number
            Receipt::create([
                'year' => $currentYear,
                'transaction_number' => $nextNumber,
                'message' => $message,
            ]);

            return $nextNumber;
        });

        $connector = new FilePrintConnector('/dev/usb/lp0');
        $printer = new Printer($connector);

        // Let me know something's coming
        $printer->feed(1);
        sleep(1);

        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->setTextSize(2, 2);
        $printer->setEmphasis(true);
        $printer->text("PING.UCHE.CA");
        $printer->feed(2);
        $printer->setTextSize(1, 1);
        $printer->setEmphasis(false);
        $printer->text('MESSAGE FOR ISAIAH UCHE');
        $printer->feed(1);

        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->text(str_repeat('-', 42));
        $printer->feed(1);

        $printer->text('TIMESTAMP: ' . now()->format('m/d/y h:i A'));
        $printer->feed(1);

        $printer->text('TRANSACTION #: ' . $currentYear . '-' . str_pad($transactionNumber, 6, '0', STR_PAD_LEFT));
        $printer->feed(1);

        $printer->text(str_repeat('-', 42));
        $printer->feed(1);

        $printer->text($message);
        $printer->feed(4);

        $printer->cut();

        $request->session()->flash('success', 'Your message was sent successfully, woohoo!');

        return redirect()->back();
    }
}
