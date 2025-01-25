<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Mpdf\Mpdf;
use Mpdf\MpdfException;
use Carbon\Carbon;

class MPdfcontroller extends Controller
{


    public function orderPdf($id)
    {
        $order = Order::with(['user', 'orderDetails', 'orderDetails.item'])->findOrFail($id);

        $mpdf = new Mpdf([
            'format' => 'A4',
            'margin_header' => '0',
            'margin_top' => '10',
            'margin_bottom' => '1',
            'margin_left' => '5',
            'margin_right' => '5',
            'margin_footer' => '10',
            'default_font' => 'sans-serif',
        ]);

        $subtotal = $order->orderDetails->sum('subtotal');

        $total_price = $order->orderDetails->sum('total');

        $total_price_in_words = $this->convertNumberToWords(floor($total_price));

        $cents = round(($total_price - floor($total_price)) * 100);
        if ($cents > 0) {
            $total_price_in_words .= ' dollars and ' . $this->convertNumberToWords($cents) . ' cents';
        } else {
            $total_price_in_words .= ' dollars';
        }

        $mpdf->SetHTMLFooter('
            <table>
                <tr>
                    <td>
                        <span>Your Company Name</span>
                    </td>
                    <td>
                        <span>Your Company Address</span>
                    </td>
                    <td>
                        <span>Your Company Email</span>
                    </td>
                    <td>
                        <span>Your Company Phone</span>
                    </td>
                </tr>
            </table>
        ');

        $mpdf->WriteHTML(View('pdf_reports.order-info', compact(
            'order',
            'subtotal',
            'total_price',
            'total_price_in_words'
        ))->render());

        $carbon_date = Carbon::parse(now())->format('d/m/Y');
        $order_name = $order->user->name . '-order-' . $carbon_date;
        $fileName = $order_name . '.pdf';
        return $mpdf->Output($fileName, 'I');
    }

    public function convertNumberToWords($number) {
        $hyphen = '-';
        $conjunction = ' and ';
        $separator = ', ';
        $negative = 'negative ';
        $decimal = ' dollars and ';
        $dictionary = [
            0 => 'zero', 1 => 'one', 2 => 'two', 3 => 'three', 4 => 'four', 5 => 'five', 6 => 'six', 7 => 'seven', 8 => 'eight', 9 => 'nine',
            10 => 'ten', 11 => 'eleven', 12 => 'twelve', 13 => 'thirteen', 14 => 'fourteen', 15 => 'fifteen', 16 => 'sixteen', 17 => 'seventeen', 18 => 'eighteen', 19 => 'nineteen',
            20 => 'twenty', 30 => 'thirty', 40 => 'forty', 50 => 'fifty', 60 => 'sixty', 70 => 'seventy', 80 => 'eighty', 90 => 'ninety',
            100 => 'hundred', 1000 => 'thousand', 1000000 => 'million', 1000000000 => 'billion', 1000000000000 => 'trillion', 1000000000000000 => 'quadrillion', 1000000000000000000 => 'quintillion'
        ];

        if (!is_numeric($number)) {
            return false;
        }

        if ($number < 0) {
            return $negative . $this->convertNumberToWords(abs($number));
        }

        $string = $fraction = null;

        if (strpos($number, '.') !== false) {
            list($number, $fraction) = explode('.', $number);
        }

        switch (true) {
            case $number < 21:
                $string = $dictionary[$number];
                break;
            case $number < 100:
                $tens = ((int) ($number / 10)) * 10;
                $units = $number % 10;
                $string = $dictionary[$tens];
                if ($units) {
                    $string .= $hyphen . $dictionary[$units];
                }
                break;
            case $number < 1000:
                $hundreds = $number / 100;
                $remainder = $number % 100;
                $string = $dictionary[$hundreds] . ' ' . $dictionary[100];
                if ($remainder) {
                    $string .= $conjunction . $this->convertNumberToWords($remainder);
                }
                break;
            default:
                $baseUnit = pow(1000, floor(log($number, 1000)));
                $numBaseUnits = (int) ($number / $baseUnit);
                $remainder = $number % $baseUnit;
                $string = $this->convertNumberToWords($numBaseUnits) . ' ' . $dictionary[$baseUnit];
                if ($remainder) {
                    $string .= $remainder < 100 ? $conjunction : $separator;
                    $string .= $this->convertNumberToWords($remainder);
                }
                break;
        }

        if (null !== $fraction && is_numeric($fraction)) {
            $string .= $decimal;
            $words = [];
            foreach (str_split((string) $fraction) as $number) {
                $words[] = $dictionary[$number];
            }
            $string .= implode(' ', $words);
        }
        else if($fraction){
            $string .= $decimal;
            $decimal .= 'dollars';

        }


        return $string;
    }

}