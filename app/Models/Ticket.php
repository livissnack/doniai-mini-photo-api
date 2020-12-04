<?php
namespace App\Models;

use ManaPHP\Db\Model;

/**
 * Class App\Models\Ticket
 */
class Ticket extends Model
{
    public $ticket_id;
    public $name;
    public $amount;
    public $qianqu;
    public $houqu;
    public $phase;
    public $updator_name;
    public $updated_time;
    public $creator_name;
    public $created_time;

    /**
     * @return string
     */
    public function getTable()
    {
        return 'ticket';
    }

    /**
     * @return string
     */
    public function getPrimaryKey()
    {
        return 'ticket_id';
    }
}