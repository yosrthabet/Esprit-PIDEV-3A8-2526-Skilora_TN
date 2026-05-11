<?php

declare(strict_types=1);

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class LatexEscapeExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('latex_escape', [$this, 'escapeLatex']),
        ];
    }

    public function escapeLatex(?string $string): string
    {
        if ($string === null || $string === '') {
            return '';
        }

        $map = [
            '\\' => '\\textbackslash{}',
            '{' => '\\{',
            '}' => '\\}',
            '$' => '\\$',
            '&' => '\\&',
            '#' => '\\#',
            '^' => '\\textasciicircum{}',
            '_' => '\\_',
            '~' => '\\textasciitilde{}',
            '%' => '\\%',
        ];

        return strtr($string, $map);
    }
}
