<?php

namespace Bgultekin\CashierFastspring\Events;

use Illuminate\Queue\SerializesModels;

class Base
{
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param string $id
     * @param string $type
     * @param bool   $live
     * @param bool   $processed
     * @param int    $created
     * @param array  $data
     *
     * @return void
     */
    public function __construct($id, $type, $live, $processed, $created, $data)
    {
        $this->id = $id;
        $this->type = $type;
        $this->live = $live;
        $this->processed = $processed;
        $this->created = $created;
        $this->data = $data;
    }
}
