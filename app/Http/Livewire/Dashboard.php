<?php

namespace App\Http\Livewire;

use App\Models\Campaign;
use App\Models\Customer;
use App\Models\Item;
use App\Models\OrderDetail;
use Carbon\Carbon;
use Livewire\Component;

class Dashboard extends Component
{
    public $monthlySalesTotal;
    public $annualSales;
    public $totalAnnualSales;
    public $successfulCampaigns;
    public $failedCampaigns;
    public $riskyCampaigns;
    public $salesWithoutCampaigns;
    public $featuredProduct;
    public $bestSellingProduct;
    public $featuredService;
    public $bestSellingService;
    public $bestPerformingMonth;
    public $bestPerformingDays;
    public $predictedBestMonth;
    public $predictedBestDay;
    public $vipCustomer;
    public $monthlySalesData;
    public $monthlySales;
    public $campaignPerformance;

    public function mount()
    {
        $this->loadData();
    }

    public function loadData()
    {
        $this->campaignPerformance = $this->getCampaignPerformance();
        $this->monthlySales = $this->getMonthlySales();
        $this->monthlySalesTotal = $this->getMonthlySalesTotal();
        $this->annualSales = $this->getAnnualSales();
        $this->totalAnnualSales = $this->getTotalAnnualSales();
        $this->successfulCampaigns = $this->getSuccessfulCampaigns();
        $this->failedCampaigns = $this->getFailedCampaigns();
        $this->riskyCampaigns = $this->getRiskyCampaigns();
        $this->salesWithoutCampaigns = $this->getSalesWithoutCampaigns();
        $this->featuredProduct = $this->getFeaturedProduct();
        $this->bestSellingProduct = $this->getBestSellingProduct();
        $this->featuredService = $this->getFeaturedService();
        $this->bestSellingService = $this->getBestSellingService();
        $this->bestPerformingMonth = $this->getBestPerformingMonth();
        $this->bestPerformingDays = $this->getBestPerformingDays();
        $this->predictedBestMonth = $this->getPredictedBestMonth();
        $this->predictedBestDay = $this->getPredictedBestDay();
        $this->vipCustomer = $this->getVIPCustomer();
        $this->monthlySalesData = $this->getMonthlySalesData();
    }

    private function getMonthlySalesTotal()
    {
        return OrderDetail::whereMonth('created_at', Carbon::now()->month)
            ->sum('total');
    }

    private function getAnnualSales()
    {
        return OrderDetail::whereYear('created_at', Carbon::now()->year)
            ->sum('total');
    }

    private function getTotalAnnualSales()
    {
        return $this->getAnnualSales();
    }

    private function getSuccessfulCampaigns()
    {
        return Campaign::withCount(['orders' => function ($query) {
            $query->withSum('orderDetails', 'total');
        }])
            ->get()
            ->filter(function ($campaign) {
                $profit = $campaign->orders_sum_order_details_total - $campaign->campaign_balance;
                $profitMargin = $campaign->campaign_balance > 0 ? ($profit / $campaign->campaign_balance) * 100 : 0;
                return $profitMargin >= 25;
            })
            ->count();
    }

    private function getFailedCampaigns()
    {
        return Campaign::withCount(['orders' => function ($query) {
            $query->withSum('orderDetails', 'total');
        }])
            ->get()
            ->filter(function ($campaign) {
                $profit = $campaign->orders_sum_order_details_total - $campaign->campaign_balance;
                $profitMargin = $campaign->campaign_balance > 0 ? ($profit / $campaign->campaign_balance) * 100 : 0;
                return $profitMargin <= 5;
            })
            ->count();
    }

    private function getRiskyCampaigns()
    {
        return Campaign::withCount(['orders' => function ($query) {
            $query->withSum('orderDetails', 'total');
        }])
            ->get()
            ->filter(function ($campaign) {
                $profit = $campaign->orders_sum_order_details_total - $campaign->campaign_balance;
                $profitMargin = $campaign->campaign_balance > 0 ? ($profit / $campaign->campaign_balance) * 100 : 0;
                return $profitMargin > 5 && $profitMargin < 25;
            })
            ->count();
    }

    private function getSalesWithoutCampaigns()
    {
        return OrderDetail::whereHas('order', function ($query) {
            $query->whereNull('campaign_id');
        })->sum('total');
    }

    private function getFeaturedProduct()
    {
        return Item::where('type', 'product')
            ->orderBy('selling_price_1', 'desc')
            ->first()
            ->name ?? 'N/A';
    }

    private function getBestSellingProduct()
    {
        return Item::where('type', 'product')
            ->withCount(['orderDetails as total_sales' => function ($query) {
                $query->select(\DB::raw('SUM(quantity * subtotal)'));
            }])
            ->orderByDesc('total_sales')
            ->first()
            ->name ?? 'N/A';
    }

    private function getFeaturedService()
    {
        return Item::where('type', 'service')
            ->orderBy('selling_price_1', 'desc')
            ->first()
            ->name ?? 'N/A';
    }

    private function getBestSellingService()
    {
        return Item::where('type', 'service')
            ->withCount(['orderDetails as total_sales' => function ($query) {
                $query->select(\DB::raw('SUM(quantity * subtotal)'));
            }])
            ->orderByDesc('total_sales')
            ->first()
            ->name ?? 'N/A';
    }

    private function getBestPerformingMonth()
    {
        $bestMonth = OrderDetail::selectRaw('MONTH(created_at) as month, SUM(total) as total')
            ->groupBy('month')
            ->orderByDesc('total')
            ->first();

        return $bestMonth ? Carbon::create()->month($bestMonth->month)->format('F') : 'N/A';
    }

    private function getBestPerformingDays()
    {
        return OrderDetail::selectRaw('DAYOFWEEK(created_at) as day, SUM(total) as total')
            ->groupBy('day')
            ->orderByDesc('total')
            ->take(3)
            ->get()
            ->map(function ($item) {
                return Carbon::create()->dayOfWeek($item->day)->format('l');
            })
            ->toArray();
    }

    private function getPredictedBestMonth()
    {
        return $this->getBestPerformingMonth();
    }

    private function getPredictedBestDay()
    {
        $bestDays = $this->getBestPerformingDays();
        return $bestDays[0] ?? 'N/A';
    }

    private function getVIPCustomer()
    {
        return Customer::withCount(['orders' => function ($query) {
            $query->whereYear('created_at', Carbon::now()->year);
        }])
            ->orderByDesc('orders_count')
            ->first()
            ->name ?? 'N/A';
    }

    private function getMonthlySalesData()
    {
        return OrderDetail::selectRaw('MONTH(created_at) as month, SUM(total) as sales')
            ->whereYear('created_at', Carbon::now()->year)
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(function ($item) {
                return [
                    'month' => Carbon::create()->month($item->month)->format('M'),
                    'sales' => $item->sales
                ];
            });
    }

    private function getMonthlySales()
    {
        return OrderDetail::selectRaw('MONTH(created_at) as month, SUM(total) as total')
            ->whereYear('created_at', Carbon::now()->year)
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(function ($item) {
                return [
                    'month' => Carbon::create()->month($item->month)->format('M'),
                    'total' => $item->total
                ];
            });
    }

    private function getCampaignStatus($profitMargin)
    {
        if ($profitMargin >= 25) return 'نجاح';
        if ($profitMargin <= 5) return 'فشل';
        return 'خطرة';
    }

    private function getCampaignPerformance()
    {
        return Campaign::withCount(['orders' => function ($query) {
            $query->withSum('orderDetails', 'total');
        }])
            ->get()
            ->map(function ($campaign) {
                $profit = $campaign->orders_sum_order_details_total - $campaign->campaign_balance;
                $profitMargin = $campaign->campaign_balance > 0 ? ($profit / $campaign->campaign_balance) * 100 : 0;
                return [
                    'name' => $campaign->campaign_name,
                    'profit' => $profit,
                    'profitMargin' => $profitMargin,
                    'status' => $this->getCampaignStatus($profitMargin)
                ];
            });
    }

    private function getProductsChartData()
    {
        return Item::where('type', 'product')
            ->withCount(['orderDetails as total_sales' => function ($query) {
                $query->select(\DB::raw('SUM(quantity * subtotal)'));
            }])
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->name,
                    'sales' => $item->total_sales ?? 0,
                    'isFeatured' => $item->name === $this->featuredProduct,
                    'isBestSelling' => $item->name === $this->bestSellingProduct
                ];
            });
    }

    private function getServicesChartData()
    {
        return Item::where('type', 'service')
            ->withCount(['orderDetails as total_sales' => function ($query) {
                $query->select(\DB::raw('SUM(quantity * subtotal)'));
            }])
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->name,
                    'sales' => $item->total_sales ?? 0,
                    'isFeatured' => $item->name === $this->featuredService,
                    'isBestSelling' => $item->name === $this->bestSellingService
                ];
            })
            ->sortByDesc('sales')
            ->take(3) // Get the top 3 services
            ->values(); // Reindex the collection
    }


    private function getDaysChartData()
    {
        $salesByDay = OrderDetail::selectRaw('DAYOFWEEK(created_at) as day, SUM(total) as total_sales')
            ->groupBy('day')
            ->get()
            ->mapWithKeys(function ($item) {
                $dayName = Carbon::create()->dayOfWeek($item->day)->format('l');
                return [$dayName => $item->total_sales];
            });

        return collect(Carbon::getDays())
            ->map(function ($day) use ($salesByDay) {
                return [
                    'name' => $day,
                    'sales' => $salesByDay[$day] ?? 0,
                    'isBestDay' => $this->predictedBestDay === $day,
                    'isTopPerforming' => in_array($day, $this->bestPerformingDays)
                ];
            });
    }

    private function getMonthsChartData()
    {
        $salesByMonth = OrderDetail::selectRaw('MONTH(created_at) as month, SUM(total) as total_sales')
            ->groupBy('month')
            ->get()
            ->mapWithKeys(function ($item) {
                $monthName = Carbon::create()->month($item->month)->format('F');
                return [$monthName => $item->total_sales];
            });

        return collect(range(1, 12))
            ->map(function ($month) use ($salesByMonth) {
                $monthName = Carbon::create()->month($month)->format('F');
                return [
                    'name' => $monthName,
                    'sales' => $salesByMonth[$monthName] ?? 0,
                    'isBestMonth' => $this->bestPerformingMonth === $monthName,
                    'isPredictedBest' => $this->predictedBestMonth === $monthName
                ];
            });
    }

    public function getChartData()
    {
        return [
            'products' => $this->getProductsChartData(),
            'services' => $this->getServicesChartData(),
            'days' => $this->getDaysChartData(),
            'months' => $this->getMonthsChartData(),
        ];
    }

    public function render()
    {
        $chartData = $this->getChartData();
        return view('livewire.stats', compact('chartData'));
    }
}