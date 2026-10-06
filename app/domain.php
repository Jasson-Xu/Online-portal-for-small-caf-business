<?php
declare(strict_types=1);

function valid_quantity(mixed $value): int {
    if (filter_var($value, FILTER_VALIDATE_INT) === false || (int)$value < 1 || (int)$value > 20) throw new DomainException('Choose a quantity between 1 and 20.');
    return (int)$value;
}
function valid_phone(string $phone): bool { return (bool)preg_match('/^\+?[0-9 ()-]{8,24}$/', $phone) && strlen(preg_replace('/\D/', '', $phone)) >= 8 && strlen(preg_replace('/\D/', '', $phone)) <= 15; }
function pickup_options(?DateTimeImmutable $now = null): array {
    $now ??= new DateTimeImmutable('now');
    $options = [];
    for ($day = 0; $day < 3; $day++) {
        $date = $now->modify("+$day days")->setTime(7, 0);
        for ($slot = $date; $slot <= $date->setTime(15, 45); $slot = $slot->modify('+15 minutes')) {
            if ($slot >= $now->modify('+15 minutes')) $options[$slot->format('Y-m-d H:i:s')] = $slot->format('D j M · g:i a');
        }
    }
    return $options;
}
function next_statuses(string $status): array {
    return match ($status) { 'received' => ['preparing', 'cancelled'], 'preparing' => ['ready', 'cancelled'], 'ready' => ['collected'], default => [] };
}
function status_label(string $status): string { return ucfirst(str_replace('_', ' ', $status)); }
