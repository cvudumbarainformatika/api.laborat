<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use Goutte\Client;
use Illuminate\Http\Request;
use Symfony\Component\DomCrawler\Crawler;

class ScrapperController extends Controller
{
    private $results = array();
    public function index()
    {
        $client = new Client();
        $url = 'https://probolinggokota.go.id/';

        $page = $client->request('GET', $url);
        // $page->filter('.card')->each(function (Crawler $item, $i) {
        //     // $this->results[$item->filter('a.btn-info')->text()] = $item->filter('img')->attr('src');
        //     $title = $item->filter('a')->text();
        //     // $link = $item->filter('a.btn-info')->attr('href');
        //     // $img = $item->filter('img')->attr('src');

        //     // PictShow::updateOrCreate(['title' => $title], ['link' => $link, 'image' => $img]);
        //     echo '<pre>';
        //     echo $title;
        //     echo '</pre>';
        // });
        // echo 'Success';
        // $titles = [];
        $page->filter('.card')->each(function (Crawler $item, $i) {
            // dump($item->text());
            // $title = $item->text();
            // $titles[] = $title;
            // array_push($titles, $title);
            $title = $item->filter('img.box-object')->attr('src');
            echo '<pre>';
            echo $title;
            echo '</pre>';

        });
    }
}
