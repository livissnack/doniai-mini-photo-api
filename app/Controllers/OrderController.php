<?php

namespace App\Controllers;

use App\Models\Order;
use App\Services\AliMarketService;
use ManaPHP\Rest\Controller;

/**
 * Class OrderController
 * @package App\Controllers
 * @property-read AliMarketService $aliMarketService
 */
class OrderController extends Controller
{
    public function indexAction()
    {
       return Order::search(['status'])
           ->where(['is_show' => Order::ENABLED_SHOW])
           ->paginate();
    }

    public function detailAction()
    {
        $order_id = input('order_id', ['int', 'default' => 0]);

        $order = Order::get($order_id);
        $express = [];
        if (!is_null($order->tracking_number) && !is_null($order->logistic_company)) {
            $express = $this->aliMarketService->express($order->tracking_number, $order->logistic_company);
        }
        return ['order' => $order, 'express' => $express];
    }
}
