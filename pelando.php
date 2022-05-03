<?php
require "vendor/autoload.php";

foreach(["hash", "bot", "key", "target"] as $item) {
    ${$item} = getenv(strtoupper($item));
}

$reader = League\Csv\Reader::createFromPath('db.csv', 'r');
$reader->setHeaderOffset(0);

$storedIds = [];
foreach ($reader->getRecords() as $item) {
    array_push($storedIds, $item['id']);
}

$writer = League\Csv\Writer::createFromPath('db.csv', 'a+');

$variables = json_encode([
    "query" => "microondas",
    "limit" => 18,
    "sortBy" => "CREATED_AT"
]);

$extensions = json_encode([
    "persistedQuery" => [
        "version" => 1,
        "sha256Hash" => $hash
    ]
]);

$client = new GuzzleHttp\Client();
$resource = $client->request('GET', 'https://www.pelando.com.br/api/graphql', [
    "query" => [
        "operationName" => "SearchOffersQuery",
        "variables" => $variables,
        "extensions" => $extensions
    ]
]);

$body = json_decode($resource->getBody()->getContents());
foreach($body->data->public->searchOffers->edges as $edge) {
    if (!in_array($edge->id, $storedIds)) {
        $writer->insertOne([
            $edge->id,
            $edge->price,
            $edge->title,
            $edge->sourceUrl
        ]);
        // Send to Telegram
        $message = "{$edge->title}\n{$edge->price}";
        $client->request('GET', "https://api.telegram.org/bot{$bot}:{$key}/sendMessage", [
            "query" => [
                "chat_id" => $target,
                "text" => $message
            ]
        ]);
    }
}