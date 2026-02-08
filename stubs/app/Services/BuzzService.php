<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class BuzzService
{
    public function getState(): array
    {
        $roundId = $this->getRoundId();
        return $this->buildState($roundId);
    }

    public function press(string $clientId, string $name): array
    {
        $roundId = $this->getRoundId();
        $pressesKey = $this->pressesKey($roundId);
        $namesKey = $this->namesKey($roundId);
        $firstTsKey = $this->firstTsKey($roundId);

        $clientId = trim($clientId);
        $name = $this->sanitizeName($name);
        $tsMs = $this->nowMs();

        $lua = <<<LUA
if redis.call('ZSCORE', KEYS[1], ARGV[1]) then
  return 0
end
redis.call('ZADD', KEYS[1], 'NX', ARGV[3], ARGV[1])
redis.call('HSET', KEYS[2], ARGV[1], ARGV[2])
redis.call('SETNX', KEYS[3], ARGV[3])
return 1
LUA;

        Redis::eval($lua, 3, $pressesKey, $namesKey, $firstTsKey, $clientId, $name, $tsMs);

        return $this->buildState($roundId);
    }

    public function reset(): array
    {
        $currentRound = $this->getRoundId();
        $nextRound = Redis::incr('buzz:round_id');

        $this->cleanupRound($currentRound);

        return $this->buildState($nextRound);
    }

    public function hardReset(): array
    {
        $this->deleteAllRounds();
        Redis::set('buzz:round_id', 1);
        // Ensure round 1 keys are clean after reset
        $this->cleanupRound(1);
        return $this->buildState(1);
    }

    private function getRoundId(): int
    {
        $roundId = Redis::get('buzz:round_id');
        if ($roundId === null) {
            Redis::set('buzz:round_id', 1);
            return 1;
        }
        return (int) $roundId;
    }

    private function buildState(int $roundId): array
    {
        $pressesKey = $this->pressesKey($roundId);
        $namesKey = $this->namesKey($roundId);
        $firstTsKey = $this->firstTsKey($roundId);

        $rawPresses = Redis::zrange($pressesKey, 0, -1, ['withscores' => true]) ?? [];

        $firstTs = Redis::get($firstTsKey);
        if ($firstTs === null && !empty($rawPresses)) {
            $firstTs = (int) array_values($rawPresses)[0];
        }

        $presses = [];
        $rank = 1;
        $winnerClientId = null;

        foreach ($rawPresses as $clientId => $score) {
            if ($rank === 1) {
                $winnerClientId = $clientId;
            }
            $name = Redis::hget($namesKey, $clientId) ?: 'Player';
            $deltaMs = $firstTs === null ? 0 : ((int) $score - (int) $firstTs);
            $presses[] = [
                'rank' => $rank,
                'clientId' => $clientId,
                'name' => $name,
                'deltaMs' => $deltaMs,
            ];
            $rank++;
        }

        return [
            'roundId' => $roundId,
            'winnerClientId' => $winnerClientId,
            'presses' => $presses,
            'serverTimeMs' => $this->nowMs(),
        ];
    }

    private function cleanupRound(int $roundId): void
    {
        Redis::del(
            $this->pressesKey($roundId),
            $this->namesKey($roundId),
            $this->firstTsKey($roundId)
        );
    }

    private function deleteAllRounds(): void
    {
        $cursor = 0;
        do {
            [$cursor, $keys] = Redis::scan($cursor, 'MATCH', 'buzz:round:*', 'COUNT', 200);
            if (!empty($keys)) {
                Redis::del($keys);
            }
        } while ($cursor !== 0);

        Redis::del('buzz:round_id');
    }

    private function pressesKey(int $roundId): string
    {
        return "buzz:round:{$roundId}:presses";
    }

    private function namesKey(int $roundId): string
    {
        return "buzz:round:{$roundId}:names";
    }

    private function firstTsKey(int $roundId): string
    {
        return "buzz:round:{$roundId}:first_ts_ms";
    }

    private function nowMs(): int
    {
        return (int) round(microtime(true) * 1000);
    }

    private function sanitizeName(string $name): string
    {
        $name = trim($name);
        $name = preg_replace('/\s+/', ' ', $name);
        return Str::limit($name, 32, '');
    }
}
