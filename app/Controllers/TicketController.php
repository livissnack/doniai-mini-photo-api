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
        return Ticket::select(['ticket_id', 'name', 'amount', 'qianqu', 'houqu', 'phase'])->paginate();
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
