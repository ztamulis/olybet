<?php

declare(strict_types= 1);

namespace App\Http\Controllers;

use App\BalanceTransaction;
use App\Bet;
use App\BetSelection;
use App\Http\Requests\ApiBetRequest;
use App\Player;
use Illuminate\Support\Carbon;

/**
 * Class BetController
 * @package App\Http\Controllers
 */
class BetController extends ApiController {

    /**
     * @param ApiBetRequest $apiBetRequest
     * @return array
     */
    public function posts(ApiBetRequest $apiBetRequest): array {
        $this->getPlayerReady($apiBetRequest);
        $this->makeBet($apiBetRequest);
        return $this->getFormattedResponseData();
    }

    /**
     * @param ApiBetRequest $apiBetRequest
     */
    private function makebet(ApiBetRequest $apiBetRequest): void {
        /** @var Player $playerModel */
        $playerModel = app(Player::class);

        $player = Player::where('player_id', $apiBetRequest->getPlayerId())->first();
        $balance = $this->getUpdatedPlayerBalance($player['balance'], $apiBetRequest->getStakeAmount());
        $nowTimestamp = Carbon::now()->timestamp;

        Bet::insert([
            'player_id' => $apiBetRequest->getPlayerId(),
            'stake_amount' => $apiBetRequest->getStakeAmount(),
            'created_at' => $nowTimestamp,
        ]);

        $betData = Bet::where(['player_id' => $apiBetRequest->getPlayerId()])->orderByDesc('created_at')->first();
        $betSelectionsData = $this->prepareBetDataForDatabase($apiBetRequest, $betData['id']);
        BetSelection::insert($betSelectionsData);

        BalanceTransaction::insert([
            'player_id' => $apiBetRequest->getPlayerId(),
            'amount' => $balance,
            'amount_before' => $player['balance']
        ]);

        $playerModel->where('player_id', $apiBetRequest->getPlayerId())->update(['balance' => $balance]);
    }

    /**
     * @param ApiBetRequest $apiBetRequest
     * @param int $betId
     * @return array
     */
    private function prepareBetDataForDatabase(ApiBetRequest $apiBetRequest, int $betId): array {
        $data = [];
        foreach ($apiBetRequest->getSelections() as $selection) {
            $data[] = [
                'bet_id' => $betId,
                'selection_id' => $selection['id'],
                'odds' => $selection['odds'],
            ];
        }
        return $data;
    }

    /**
     * @param ApiBetRequest $apiBetRequest
     */
    private function getPlayerReady(ApiBetRequest $apiBetRequest): void {
        $player = Player::where('player_id', $apiBetRequest->getPlayerId())->first();
        if (empty($player)) {
            Player::insert([
                'player_id' => $apiBetRequest->getPlayerId(),
            ]);
        }
    }

    /**
     * @param float $oldBalance
     * @param float $stakeAmount
     * @return float
     */
    private function getUpdatedPlayerBalance(float $oldBalance, float $stakeAmount): float {
        return $oldBalance - $stakeAmount;
    }
}