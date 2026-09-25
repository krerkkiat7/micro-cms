<?php

namespace App\Support\Front\Views;

use Illuminate\Support\Facades\Redis;

/**
 * คิวการเข้าชมบน Redis list (`front:views:{type}`) — RPUSH ตอนมีผู้เข้าชม (O(1) ไม่แตะฐานข้อมูล),
 * ดึงออกเป็นชุดด้วย Lua script (LRANGE + LTRIM ใน atomic step เดียว — worker หลายตัวไม่ได้รายการซ้ำกัน)
 */
final class RedisViewBuffer implements ViewBuffer
{
    private const PULL_SCRIPT = <<<'LUA'
local items = redis.call('LRANGE', KEYS[1], 0, tonumber(ARGV[1]) - 1)
if #items > 0 then
    redis.call('LTRIM', KEYS[1], #items, -1)
end
return items
LUA;

    public function __construct(private readonly string $connection = 'default') {}

    public function push(string $type, array $row): void
    {
        $this->redis()->rpush($this->key($type), json_encode($row, JSON_UNESCAPED_UNICODE));
    }

    public function pull(string $type, int $max): array
    {
        $items = $this->redis()->eval(self::PULL_SCRIPT, 1, $this->key($type), $max);

        return array_values(array_filter(array_map(
            fn ($item) => is_string($item) ? json_decode($item, true) : null,
            is_array($items) ? $items : [],
        ), 'is_array'));
    }

    public function restore(string $type, array $rows): void
    {
        if ($rows === []) {
            return;
        }

        $this->redis()->rpush($this->key($type), ...array_map(fn (array $row) => json_encode($row, JSON_UNESCAPED_UNICODE), $rows));
    }

    public function size(string $type): int
    {
        return (int) $this->redis()->llen($this->key($type));
    }

    private function redis()
    {
        return Redis::connection($this->connection);
    }

    private function key(string $type): string
    {
        return "front:views:{$type}";
    }
}
