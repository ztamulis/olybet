<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Throwable;

/**
 * Class ApiController
 * @package App\Http\Controllers\Api
 */
class ApiController extends Controller {

    /**
     * @var array
     */
    protected $defaultResponseArray = [
        'status' => [
            'HTTP Code' => 201,
            'Content' => [],
        ],
    ];

    /**
     * @param $data
     * @param int $errorCode
     * @param string $message
     * @param Throwable|null $exception
     * @return array
     */
    public function getFormattedResponseData(
        $data = null,
        int $errorCode = 0,
        string $message = "",
        Throwable $exception = null
    ): array {
        if ($data !== null) {
            $this->defaultResponseArray['data'] = $data;
        }
//        $this->defaultResponseArray['status']['timestamp'] = Carbon::now()->timestamp;
//
//        $this->defaultResponseArray['status']['error_code'] = $errorCode;
//        if ($errorCode == 0 && $exception !== null) {
//            $this->defaultResponseArray['status']['error_code'] = $exception->getCode();
//        }
//
//        $this->defaultResponseArray['status']['error_message'] = $message;
//        if ($message == "" && $exception !== null) {
//            $this->defaultResponseArray['status']['error_message'] = $exception->getMessage();
//        }

        return $this->defaultResponseArray;
    }

//    /**
//     * @param string $action
//     * @return string
//     */
//    public static function getAction(string $action): string {
//        if (!method_exists(static::class, $action)) {
//            trigger_error('Method "' . $action . '" does not exist in class "' . static::class . '"', E_USER_ERROR);
//        }
//        return static::class . '@' . $action;
//    }
}