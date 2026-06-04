<?php

class Validator {
    public static function string(string $value, int $min = 0, int $max = 1000): string {
        $v = trim($value);
        $len = mb_strlen($v);
        if ($len < $min || $len > $max) {
            throw new InvalidArgumentException("Value must be between $min and $max characters.");
        }
        return $v;
    }

    public static function username(string $value): string {
        $v = trim($value);
        if (!preg_match('/^[a-zA-Z0-9_.-]{3,50}$/', $v)) {
            throw new InvalidArgumentException('Username must be 3-50 characters: letters, numbers, dots, hyphens, underscores.');
        }
        return $v;
    }

    public static function email(string $value): string {
        $v = trim($value);
        if ($v === '') return '';
        if (!filter_var($v, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email address.');
        }
        if (mb_strlen($v) > 254) {
            throw new InvalidArgumentException('Email address is too long.');
        }
        return $v;
    }

    public static function password(string $value, int $min = 6): string {
        if (mb_strlen($value) < $min) {
            throw new InvalidArgumentException("Password must be at least $min characters.");
        }
        if (mb_strlen($value) > 128) {
            throw new InvalidArgumentException('Password is too long (max 128 characters).');
        }
        return $value;
    }

    public static function int(string $value, int $min = 0, int $max = 2147483647): int {
        if (!ctype_digit(ltrim($value, '-')) && !ctype_digit($value)) {
            throw new InvalidArgumentException('Value must be an integer.');
        }
        $v = (int) $value;
        if ($v < $min || $v > $max) {
            throw new InvalidArgumentException("Value must be between $min and $max.");
        }
        return $v;
    }

    public static function bool($value): bool {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public static function slug(string $value): string {
        $v = trim($value);
        if ($v !== '' && !preg_match('/^[a-z0-9-]+$/', $v)) {
            throw new InvalidArgumentException('Invalid slug format.');
        }
        return $v;
    }

    public static function phone(string $value): string {
        $v = trim($value);
        if ($v === '') return '';
        $v = preg_replace('/[^0-9+]/', '', $v);
        if (!preg_match('/^\+?[0-9]{7,15}$/', $v)) {
            throw new InvalidArgumentException('Invalid phone number format.');
        }
        return $v;
    }

    public static function inArray(string $value, array $allowed): string {
        if (!in_array($value, $allowed, true)) {
            throw new InvalidArgumentException('Invalid value: ' . htmlspecialchars($value));
        }
        return $value;
    }

    public static function filename(string $value): string {
        $v = preg_replace('/[^\w.-]/', '_', $value);
        $v = ltrim($v, '.');
        if ($v === '') {
            throw new InvalidArgumentException('Invalid filename.');
        }
        return $v;
    }

    public static function text(string $value, int $max = 10000): string {
        $v = trim($value);
        if (mb_strlen($v) > $max) {
            throw new InvalidArgumentException("Text exceeds maximum length of $max characters.");
        }
        return strip_tags($v);
    }

    public static function url(string $value): string {
        $v = trim($value);
        if ($v !== '' && !filter_var($v, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('Invalid URL format.');
        }
        return $v;
    }
}
