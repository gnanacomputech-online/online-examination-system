<?php

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function redirect(string $route): void
{
    header('Location: /index.php?route=' . $route);
    exit;
}

function flash_set(string $type, string $message): void
{
    $_SESSION['flash'][$type] = $message;
}

function flash_get(string $type): ?string
{
    if (empty($_SESSION['flash'][$type])) {
        return null;
    }
    $message = $_SESSION['flash'][$type];
    unset($_SESSION['flash'][$type]);
    return $message;
}

function old(array $data, string $key, string $default = ''): string
{
    return e($data[$key] ?? $default);
}

function old_input_set(array $data): void
{
    $_SESSION['old_input'] = $data;
}

function old_input_get(): array
{
    if (empty($_SESSION['old_input'])) {
        return [];
    }
    $data = $_SESSION['old_input'];
    unset($_SESSION['old_input']);
    return $data;
}
