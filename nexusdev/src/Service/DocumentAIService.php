<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class DocumentAIService
{
    private HttpClientInterface $client;
    private string $apiKey;

    public function __construct(HttpClientInterface $client, string $apiKey)
    {
        $this->client = $client;
        $this->apiKey = $apiKey;
    }

    /**
     * Translate text using LibreTranslate free public API.
     *
     * @param string $text Text to translate
     * @param string $targetLang Target language code (e.g., 'en', 'fr', 'es')
     * @return string Translated text
     * @throws \Exception If the API call fails
     */
    public function translate(string $text, string $targetLang = 'fr'): string
    {
        // MyMemory free translation API (no key required, 10k chars/day)
        $url = 'https://api.mymemory.translated.net/get';

        // Default to English source unless we detect otherwise
        $sourceLang = 'en';
        // You can add simple detection logic here if needed

        $response = $this->client->request('GET', $url, [
            'query' => [
                'q' => $text,
                'langpair' => $sourceLang . '|' . $targetLang,
            ],
            'timeout' => 8,
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new \Exception('MyMemory API request failed: ' . $response->getContent(false));
        }

        $data = $response->toArray();
        return $data['responseData']['translatedText'] ?? $text;
    }
}
