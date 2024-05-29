<?php

use Illuminate\Support\Facades\Cache;
use Stichoza\GoogleTranslate\GoogleTranslate;

if (!function_exists('translateText')) {
    function translateText(string $text): string
    {
        $locale = app()->getLocale();

        // Check if the text is already in the target language
        if (($locale === 'fr' && isTextFrench($text)) || ($locale === 'en' && !isTextFrench($text))) {
            return $text;
        }

        $cacheKey = 'translated_text_' . md5($text . $locale);

        // Check if the translated text is cached
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $translator = app()->make(GoogleTranslate::class);
        $translator->setSource(null); // Detect language automatically
        $translator->setTarget($locale); // Set the target language to the current locale

        // Translate the text
        $textTranslated = $translator->translate($text);

        // Cache the translated text for future use
        Cache::put($cacheKey, $textTranslated, now()->addDay());

        return $textTranslated;
    }

    function isTextFrench(string $text): bool
    {
        $cacheKey = 'detected_language_' . md5($text);

        // Check if the detected language is cached
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey) === 'fr';
        }

        $translator = app()->make(GoogleTranslate::class);
        $translator->setSource(null); // Auto-detect the source language

        // Perform the translation to detect the language
        $translator->translate($text);
        $detectedLanguage = $translator->getLastDetectedSource();

        // Cache the detected language for future use
        Cache::put($cacheKey, $detectedLanguage, now()->addDay());

        return $detectedLanguage === 'fr';
    }
}
