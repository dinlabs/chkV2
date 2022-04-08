<?php

namespace App\Twig;

use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function getFilters()
    {
        return [
            new TwigFilter('excerpt', [$this, 'getExcerpt'], ['is_safe' => ['html']]),
        ];
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('t2sHash', [$this, 'hashEmail']),
        ];
    }

    public function getExcerpt($text, $words = 100, $link = null)
    {
        $excerpt = explode(' ', $text, $words);
        if(count($excerpt) >= $words) 
        {
            array_pop($excerpt);
            $excerpt = implode(' ', $excerpt) . '...';
        }
        else
        {
            $excerpt = implode(' ', $excerpt);
        }	
        $excerpt = preg_replace('`\[[^\]]*\]`', '', $excerpt);

        if(!is_null($link))
        {
            $excerpt .= " <a href=\"$link\">" .  $this->translator->trans('app.front.readmore') . "</a>.";
        }

        return "<p>$excerpt</p>";
    }

    /** pour Target2Sell */
    public function hashEmail($string): string
    {
        return $string ? strtoupper(hash('SHA256', $string)) : '';
    }
}