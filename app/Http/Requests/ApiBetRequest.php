<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Player;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class ApiBetRequest
 * @package Cryptofy\Coinspace\Controllers\Api\Requests
 */
class ApiBetRequest extends FormRequest {

    const DEFAULT_PLAYER_BALANCE = 1000;
    const MAXIMUM_WIN_AMOUNT = 20000;
    const UNKNOWN_ERROR_MESSAGE = 'Unknown error';
    const INSUFFICIENT_BALANCE_MESSAGE = 'Insufficient balance';
    const MAXIMUM_WIN_AMOUNT_MESSAGE = 'Maximum win amount is 20000';

    /**
     * @var bool
     */
    protected $shouldValidateRouteParameters = false;

    /**
     * @var array
     */
    private static $customErrorCodes = [
        'Unknown error' => 0,
        'Betslip structure mismatch' => 1,
        'Minimum stake amount is 0.3' => 2,
        'Maximum stake amount is 10000' => 3,
        'Minimum number of selections is 1' => 4,
        'Maximum number of selections is 20' => 5,
        'Minimum odds are 1' => 6,
        'Maximum odds are 10000' => 7,
        'Duplicate selection found' => 8,
        'Maximum win amount is 20000' => 9,
        'Insufficient balance' => 11,
    ];

    /**
     * @return array
     */
    public function rules(): array {
        return [
            'player_id' => 'required|numeric',
            'stake_amount' => 'required|numeric|min:0.3|max:10000',
            'selections' => 'required|array|min:1|max:20',
            'selections.*.id' => 'required|distinct',
            'selections.*.odds' => 'required|numeric|min:1|max:10000',
        ];
    }

    /**
     * @return array
     */
    public function messages() {
        return [
            'player_id.required' => 'Betslip structure mismatch',
            'stake_amount.required' => 'Betslip structure mismatch',
            'selections.required' => 'Betslip structure mismatch',
            'selections.array' => 'Betslip structure mismatch',
            'selections.*.id.required' => 'Betslip structure mismatch',
            'selections.*.odds.required' => 'Betslip structure mismatch',
            'stake_amount.min' => 'Minimum stake amount is :min',
            'stake_amount.max' => 'Maximum stake amount is :max',
            'selections.min' => 'Minimum number of selections is :min',
            'selections.max' => 'Maximum number of selections is :max',
            'selections.*.odds.min' => 'Minimum odds are :min',
            'selections.*.odds.max' => 'Maximum odds are :max',
            'selections.*.id.distinct' => 'Duplicate selection found',
        ];
    }

    /**
     * @return float
     */
    public function getStakeAmount(): float {
        return (float)$this->input('stake_amount');
    }

    /**
     * @return int
     */
    public function getPlayerId(): int {
        return (int)$this->input('player_id');
    }

    /**
     * @return array
     */
    public function getSelections(): array {
        return $this->input('selections');
    }

    /**
     * @return bool
     */
    protected function passesAuthorization(): bool {
        if (method_exists($this, 'authorize')) {
            return $this->authorize();
        }
        return false;
    }

    /**
     * @return bool
     */
    protected function authorize(): bool {
        return true;
    }

    /**
     * @param null $keys
     * @return array
     */
    public function all($keys = null): array {
        if ($this->shouldValidateRouteParameters) {
            return array_replace_recursive(
                parent::all($keys),
                $this->route()->parameters()
            );
        }

        return parent::all($keys);
    }

    /**
     * @param Validator $validator
     * @throws ValidationException
     */
    protected function failedValidation(Validator $validator) {
        $formattedErrors = $this->getFormattedErrors($validator);
        $response = new JsonResponse([
            'errors' => $formattedErrors['errors'],
            'selections' => $formattedErrors['selections'],
        ]);
        throw new ValidationException($validator, $response);
    }

    /**
     * @param Validator $validator
     * @return array
     */
    private function getFormattedErrors(Validator $validator): array {
        $formattedErrors['errors'] = [];
        $formattedErrors['selections'] = [];
        foreach ($validator->getMessageBag()->toArray() as $key => $errors) {
            if (strpos($key, 'selections') !== false && !empty($this->input('selections'))) {
                $formattedErrors['selections'][] = [
                    'id' => $this->getSelectionId($key),
                    'errors' => $this->getError($errors),
                ];
            } else {
                $formattedErrors['errors'][] = $this->getError($errors);
            }
        }
        return $formattedErrors;
    }

    /**
     * @param array $errors
     * @return array
     */
    private function getError(array $errors): array {
        $formattedErrors = [];
        foreach ($errors as $error) {
            $code = self::$customErrorCodes[self::UNKNOWN_ERROR_MESSAGE];
            $message = self::UNKNOWN_ERROR_MESSAGE;

            if (isset(self::$customErrorCodes[$error])) {
                $code = self::$customErrorCodes[$error];
                $message = $error;
            }
            $formattedErrors[] = [
                'code' => $code,
                'message' => $message,
            ];
        }
        return $formattedErrors;
    }


    /**
     * @param string $key
     * @return string
     */
    private function getSelectionId(string $key): string {
        $selectionsKey = explode('.', $key)[1];
        return $this->getSelections()[$selectionsKey]['id'];
    }

    /**
     * @param $validator
     */
    public function withValidator($validator): void {
        $validator->after(function ($validator) {
            if (!$this->hasEnoughBalance()) {
                $validator->errors()->add('balance', self::INSUFFICIENT_BALANCE_MESSAGE);
            }
            if (empty($this->input('selections'))) {
                return;
            }
            if ($this->getWinAmount() > self::MAXIMUM_WIN_AMOUNT) {
                $validator->errors()->add('win_amount', self::MAXIMUM_WIN_AMOUNT_MESSAGE);
            }

        });
    }

    /**
     * @return bool
     */
    private function hasEnoughBalance(): bool {
        $stakeAmount = $this->getStakeAmount();
        if (empty($stakeAmount)) {
            return false;
        }

        $player = Player::where('player_id', $this->getPlayerId())->first();
        if (empty($player)) {
            if ($stakeAmount > self::DEFAULT_PLAYER_BALANCE) {
                return false;
            }
            return true;
        }

        if ($player['balance'] < $stakeAmount) {
            return false;
        }

        return true;
    }

    /**
     * @return float
     */
    private function getWinAmount(): float {
        $stakeAmount = $this->getStakeAmount();
        foreach ($this->getSelections() as $value) {
            $stakeAmount *= $value['odds'];
        }
        return $stakeAmount;
    }
}
