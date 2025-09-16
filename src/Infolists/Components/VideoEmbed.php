<?php

namespace Tapp\FilamentLibrary\Infolists\Components;

use Filament\Infolists\Components\Entry;

class VideoEmbed extends Entry
{
    protected string $view = 'filament-library::infolists.components.video-embed';

    public function getVideoEmbedHtml(string $url): string
    {
        if (str_contains($url, 'youtube.com') || str_contains($url, 'youtu.be')) {
            $videoId = $this->getYoutubeVideoId($url);

            return '<div class="relative w-full" style="padding-bottom: 56.25%;"><iframe class="absolute top-0 left-0 w-full h-full" src="https://www.youtube.com/embed/' . $videoId . '" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></div>';
        } elseif (str_contains($url, 'vimeo.com')) {
            $videoId = $this->getVimeoVideoId($url);
            $hash = $this->getVimeoHash($url);

            return '<div class="relative w-full" style="padding-bottom: 56.25%;"><iframe class="absolute top-0 left-0 w-full h-full" src="https://player.vimeo.com/video/' . $videoId . '?h=' . $hash . '&badge=0&autopause=0&player_id=0&app_id=58479" frameborder="0" allow="autoplay; fullscreen; picture-in-picture; clipboard-write; encrypted-media" allowfullscreen></iframe></div>';
        } elseif (str_contains($url, 'wistia.com')) {
            $videoId = $this->getWistiaVideoId($url);

            return '<div class="relative w-full" style="padding-bottom: 56.25%;"><iframe class="absolute top-0 left-0 w-full h-full" src="https://fast.wistia.net/embed/iframe/' . $videoId . '" frameborder="0" allow="autoplay; fullscreen" allowfullscreen></iframe></div>';
        }

        return '';
    }

    private function getYoutubeVideoId(string $url): string
    {
        if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $url, $matches)) {
            return $matches[1];
        }

        return '';
    }

    private function getVimeoVideoId(string $url): string
    {
        if (preg_match('/vimeo\.com\/(\d+)(?:\/[a-zA-Z0-9]+)?/', $url, $matches)) {
            return $matches[1];
        }

        return '';
    }

    private function getWistiaVideoId(string $url): string
    {
        if (preg_match('/wistia\.com\/(?:medias|embed\/iframe)\/([a-zA-Z0-9]+)/', $url, $matches)) {
            return $matches[1];
        }

        return '';
    }

    private function getVimeoHash(string $url): string
    {
        if (preg_match('/vimeo\.com\/\d+\/([a-zA-Z0-9]+)/', $url, $matches)) {
            return $matches[1];
        }

        return '';
    }
}
