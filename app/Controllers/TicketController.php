<?php

namespace App\Controllers;

use App\Models\Ticket;
use App\Services\TicketService;
use ManaPHP\Rest\Controller;

/**
 * Class TicketController
 * @package App\Controllers
 * @property-read TicketService $ticketService
 */
class TicketController extends Controller
{
    public function indexAction()
    {
        $data = Ticket::select(['ticket_id', 'name', 'amount', 'qianqu', 'houqu', 'phase'])->paginate();

        foreach ($data->items as $k => &$v) {
            if (is_string($v['qianqu']) && !is_null($v['qianqu'])) {
                $v['qianqu'] = explode(' ', $v['qianqu']);
            }
        }
        return $data;
    }

    public function randomAction()
    {
        $user_id = $this->identity->getId();
        if ($user_id < 0) {
            return '用户未授权登录';
        }
        return $this->ticketService->doubleBall();
    }
}
