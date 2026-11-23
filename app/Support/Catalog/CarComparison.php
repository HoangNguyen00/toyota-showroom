<?php

namespace App\Support\Catalog;

/**
 * Dựng các dòng so sánh từ hai mảng đã qua CarPresenter (allowlist), không đọc model trực tiếp.
 * Mỗi dòng: label, values [xe1, xe2], different. Giá trị thiếu hiển thị "Đang cập nhật".
 */
final class CarComparison
{
    public const MISSING = 'Đang cập nhật';

    /** Nhãn specs trùng với trường nền đã hiển thị, không lặp lại. */
    private const BASE_LABELS = ['dòng xe', 'năm sản xuất', 'năm', 'màu ngoại thất', 'màu', 'mã xe', 'mã chiếc xe', 'giá', 'tình trạng'];

    /**
     * @param  array{0: array, 1: array}  $cars  Kết quả CarPresenter::fromCar theo thứ tự khách chọn.
     * @return array{cars: array, rows: list<array{label: string, values: array{0: string, 1: string}, different: bool}>, price_gap: int}
     */
    public static function build(array $cars): array
    {
        [$first, $second] = $cars;

        $rows = [
            self::row('Giá công bố tham khảo', self::price($first['price']), self::price($second['price'])),
            self::row('Dòng xe', $first['category_name'], $second['category_name']),
            self::row('Năm sản xuất', $first['year'], $second['year']),
            self::row('Màu ngoại thất', $first['color'], $second['color']),
            self::row('Tình trạng', $first['status_label'], $second['status_label']),
            self::row('Mã chiếc xe', $first['code'], $second['code']),
        ];

        $firstSpecs = self::specs($first['specs']);
        $secondSpecs = self::specs($second['specs']);
        $labels = array_values(array_unique(array_merge(array_keys($firstSpecs), array_keys($secondSpecs))));
        foreach ($labels as $key) {
            $label = $firstSpecs[$key]['label'] ?? $secondSpecs[$key]['label'];
            $rows[] = self::row($label, $firstSpecs[$key]['value'] ?? null, $secondSpecs[$key]['value'] ?? null);
        }

        return [
            'cars' => [$first, $second],
            'rows' => $rows,
            'price_gap' => abs($first['price'] - $second['price']),
        ];
    }

    /** @return array<string, array{label: string, value: string}> khóa theo nhãn chuẩn hóa */
    private static function specs(mixed $specs): array
    {
        $result = [];
        foreach (is_array($specs) ? $specs : [] as $spec) {
            $label = is_array($spec) ? trim((string) ($spec['label'] ?? '')) : '';
            $key = mb_strtolower($label);
            if ($label === '' || in_array($key, self::BASE_LABELS, true) || isset($result[$key])) {
                continue;
            }
            $result[$key] = ['label' => $label, 'value' => trim((string) ($spec['value'] ?? ''))];
        }

        return $result;
    }

    private static function row(string $label, mixed $first, mixed $second): array
    {
        $values = [self::display($first), self::display($second)];

        return [
            'label' => $label,
            'values' => $values,
            'different' => mb_strtolower($values[0]) !== mb_strtolower($values[1]),
        ];
    }

    private static function display(mixed $value): string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? self::MISSING : $value;
    }

    private static function price(int $price): string
    {
        return $price > 0 ? number_format($price, 0, ',', '.').' ₫' : '';
    }
}
