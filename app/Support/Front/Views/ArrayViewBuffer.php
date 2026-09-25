<?php

namespace App\Support\Front\Views;

/**
 * คิวในหน่วยความจำ (อยู่แค่ใน process เดียว) — ใช้ในเทสเพื่อทดสอบการพักคิว + flush เป็นชุดโดยไม่ต้องมี Redis
 */
final class ArrayViewBuffer implements ViewBuffer
{
    /** @var array<string, list<array<string, mixed>>> */
    private array $queues = [];

    public function push(string $type, array $row): void
    {
        $this->queues[$type][] = $row;
    }

    public function pull(string $type, int $max): array
    {
        $items = array_slice($this->queues[$type] ?? [], 0, $max);
        $this->queues[$type] = array_slice($this->queues[$type] ?? [], count($items));

        return $items;
    }

    public function restore(string $type, array $rows): void
    {
        $this->queues[$type] = [...($this->queues[$type] ?? []), ...$rows];
    }

    public function size(string $type): int
    {
        return count($this->queues[$type] ?? []);
    }
}
