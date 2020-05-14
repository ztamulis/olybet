<?php

declare(strict_types=1);

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * Class BalanceTransaction
 * @package App
 */
class BalanceTransaction extends Model {

    /**
     * @var bool
     */
    public $timestamps = true;

    /**
     * @var string
     */
    protected $table = 'balance_transactions';

    /**
     * @var array
     */
    protected $fillable = ['name'];

}
