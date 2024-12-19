<?php

namespace App\Concerns;

use DOMDocument;

trait CanProcessDescription {

    function processDescription($htmlContent)
    {
        $dom = new DOMDocument();
        // Load HTML content
        libxml_use_internal_errors(true);
        $dom->loadHTML(mb_convert_encoding($htmlContent, 'HTML-ENTITIES', 'UTF-8'));
        libxml_clear_errors();

        // Get all <a> elements
        $links = $dom->getElementsByTagName('a');
        $imgs = $dom->getElementsByTagName('img');

        foreach ($links as $link) {
            // Set target attribute to _blank and style to color blue
            $link->setAttribute('target', '_blank');
            $link->setAttribute('style', 'color: blue;');
        }

        foreach ($imgs as $img) {
            // Set lazy loading attribute to lazy
            $img->setAttribute('loading', 'lazy');

            // Check if img is inside an <a> element
            $parent = $img->parentNode;
            if ($parent->nodeName !== 'a') {
                // Create <a> element
                $a = $dom->createElement('a');
                $a->setAttribute('href', $img->getAttribute('src'));
                $a->setAttribute('target', '_blank');
                $a->setAttribute('style', 'color: blue;');

                // Replace img with the new <a> element containing the img
                $parent->replaceChild($a, $img);
                $a->appendChild($img);
            }
        }

        // Convert text URLs to <a> elements with target="_blank" and style
        $body = $dom->getElementsByTagName('body')->item(0);
        $this->convertTextUrlsToLinks($body, $dom);

        // Save and return modified HTML
        return $dom->saveHTML($dom->documentElement);
    }

    function convertTextUrlsToLinks($node, $dom)
    {
        if ($node->nodeType == XML_TEXT_NODE) {
            $text = $node->nodeValue;

            // Vérifiez que le parent n'est pas déjà une balise <a>
            if ($node->parentNode->nodeName !== 'a') {
                $newHtml = preg_replace(
                    '#(https?://[^\s<]+)#i',
                    '<a href="$1" target="_blank" style="color: blue;">$1</a>',
                    htmlspecialchars($text, ENT_QUOTES, 'UTF-8')
                );

                if ($newHtml !== htmlspecialchars($text, ENT_QUOTES, 'UTF-8')) {
                    $newFragment = $dom->createDocumentFragment();
                    $newFragment->appendXML($newHtml);
                    $node->parentNode->replaceChild($newFragment, $node);
                }
            }
        } elseif ($node->nodeType == XML_ELEMENT_NODE) {
            foreach ($node->childNodes as $child) {
                $this->convertTextUrlsToLinks($child, $dom);
            }
        }
    }

}
