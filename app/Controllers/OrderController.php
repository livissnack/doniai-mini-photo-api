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
        $user_id = $this->identity->getId();
        if ($user_id < 0) {
            return '用户未授权登录';
        }

        return Order::search(['status'])
           ->where(['is_show' => Order::ENABLED_SHOW, 'user_id' => $user_id])
           ->paginate();
    }

    public function deleteAction()
    {
        $user_id = $this->identity->getId();
        if ($user_id < 0) {
            return '用户未授权登录';
        }
        return Order::rUpdate(['is_show' => Order::UNENABLED_SHOW]);
    }

    public function detailAction()
    {
        $order_id = input('order_id', ['int', 'default' => 0]);
        $user_id = $this->identity->getId();
        if ($user_id < 0) {
            return '用户未授权登录';
        }

        return Order::get($order_id);
    }

    public function expressAction()
    {
        $user_id = $this->identity->getId();
        if ($user_id < 0) {
            return '用户未授权登录';
        }
        $tracking_number = input('tracking_number', ['string', 'default' => '']);
        $logistic_company = input('logistic_company', ['string', 'default' => '']);
        return $this->aliMarketService->express($tracking_number, $logistic_company);
    }
}
