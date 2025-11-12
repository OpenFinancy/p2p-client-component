<?php

declare(strict_types=1);

namespace OpenFinancy\Component\P2PClient\Metadata;

/**
 * Normalises metadata arrays (trims strings, deduplicates lists, removes empty values).
 */
final class MetadataNormalizer
{
    /**
     * @param string[] $listKeys
     */
    public function __construct(private readonly array $listKeys = [
        'managed_pairs',
        'supported_pairs',
        'supported_assets',
        'metals',
        'categories',
        'brands',
        'supported_collections',
        'tags',
    ])
    {
    }

    /**
     * @param array<string, mixed> $metadata
     *
     * @return array<string, mixed>
     */
    public function normalize(array $metadata): array
    {
        $normalized = [];

        foreach ($metadata as $key => $value) {
            if (is_string($value)) {
                $trimmed = trim($value);

                if ($trimmed !== '') {
                    $normalized[$key] = $trimmed;
                }

                continue;
            }

            if (is_array($value)) {
                $normalized[$key] = $this->normalizeArray($key, $value);
                continue;
            }

            if ($value !== null) {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }

    /**
     * @param array<int|string, mixed> $value
     *
     * @return array<int|string, mixed>
     */
    private function normalizeArray(string $key, array $value): array
    {
        if ($this->isSequential($value) || in_array($key, $this->listKeys, true)) {
            $list = [];

            foreach ($value as $item) {
                if (is_string($item)) {
                    $item = trim($item);

                    if ($item === '') {
                        continue;
                    }
                }

                if ($item === null || (is_array($item) && $item === [])) {
                    continue;
                }

                $list[] = $item;
            }

            return array_values(array_unique($list, SORT_REGULAR));
        }

        $result = [];

        foreach ($value as $nestedKey => $nestedValue) {
            if (is_string($nestedValue)) {
                $trimmed = trim($nestedValue);

                if ($trimmed !== '') {
                    $result[$nestedKey] = $trimmed;
                }

                continue;
            }

            if (is_array($nestedValue)) {
                $result[$nestedKey] = $this->normalizeArray((string) $nestedKey, $nestedValue);
                continue;
            }

            if ($nestedValue !== null) {
                $result[$nestedKey] = $nestedValue;
            }
        }

        return $result;
    }

    /**
     * @param array<int|string, mixed> $value
     */
    private function isSequential(array $value): bool
    {
        return array_keys($value) === range(0, count($value) - 1);
    }
}
