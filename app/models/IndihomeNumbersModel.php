<?php
declare(strict_types=1);

use Phalcon\Mvc\Model;

class IndihomeNumbersModel extends Model
{
    public $id;
    public $subscription_number;
    public $created_at;
    public $updated_at;
    public $deleted_at;

    public function initialize(): void
    {
        $this->setSource('indihome_numbers');
        $this->useDynamicUpdate(true);
    }


    public function beforeCreate(): void
    {
        $this->created_at = date('Y-m-d H:i:s');
        $this->updated_at = date('Y-m-d H:i:s');
    }

    public function beforeUpdate(): void
    {
        $this->updated_at = date('Y-m-d H:i:s');
    }
}