<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Goutte\Client;
use Symfony\Component\HttpClient\HttpClient;
use DateTime;

class AmazonScrapeController extends Controller
{
    public function scrape($asin)
    {
        // Defina os headers para simular um navegador real
        $headers = [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.3',
            'Accept-Language' => 'en-US,en;q=0.9',
        ];

        $client = new Client(HttpClient::create(['headers' => $headers]));

        $url = "https://www.amazon.com.br/dp/$asin";

        $crawler = $client->request('GET', $url);

        $title = $crawler->filter('#productTitle')->text();

        $price = $crawler->filter("#corePrice_feature_div")->text();
        $price = explode("R$", $price);
        $price = "R$ " . $price[1];

        if ($crawler->filter("#cm-cr-dp-review-header")->text()) {
            $review = $crawler->filter("#cm-cr-dp-review-header")->text();
            $reviewNumber = "Nenhuma avaliação disponível";
        } else {
            $review = $crawler->filter("#acrPopover")->text();
            $review = explode(" ", $review);
            $review = $review[1] . " " . $review[2] . " " . $review[3] . " " . $review[4];

            $reviewNumber = $crawler->filter("#acrCustomerReviewText")->text();
        }

        $enviadoVendido = $crawler->filter(".a-size-small.tabular-buybox-text-message");

        $infos = [];
        foreach ($enviadoVendido as $element) {
            $infos[] = $element->textContent;
        }
        $enviado = $infos[1];
        $vendido = $infos[2];

        if( ($enviado == 'Amazon.com.br' || $enviado == 'Amazon') && ($vendido == 'Amazon.com.br' || $vendido == 'Amazon') ){
            $logistica = "FBA";
        }
        else if( ($enviado == 'Amazon.com.br' || $enviado == 'Amazon') && $vendido == $vendido){
            $logistica = "FBA";
        }
        else if($enviado == $vendido && $vendido == $vendido){
            $logistica = "DBA";
        }  

        $quantidade = $crawler->filter("#quantity")->text();
        $quantidade = explode(" ", $quantidade);
        $quantidade = end($quantidade);

        $dataEntrega = $crawler->filter('span[data-csa-c-type="element"][data-csa-c-content-id="DEXUnifiedCXPDM"] span.a-text-bold')->text();

        $marca = $crawler->filter('a#bylineInfo')->text();
        
        if (strpos($marca, "Visite a loja ") !== false) {
            $marca = explode("Visite a loja ", $marca);
        } else {
            $marca = explode("Marca: ", $marca);
        }
        $marca = end($marca);

        //$titulo = $crawler->filter('.a-size-medium.a-spacing-small.secHeader h1')->text();
        $disponivelDesde = $crawler->filter('tr th:contains("Disponível para compra desde") + td');
        if($disponivelDesde->count() > 0){
            $disponivelDesde = $crawler->filter('tr th:contains("Disponível para compra desde") + td')->text();
            $dataFormatada = explode(" ", $disponivelDesde); 

            $monthNames = [
                "janeiro" => 1,
                "fevereiro" => 2,
                "março" => 3,
                "abril" => 4,
                "maio" => 5,
                "junho" => 6,
                "julho" => 7,
                "agosto" => 8,
                "setembro" => 9,
                "outubro" => 10,
                "novembro" => 11,
                "dezembro" => 12
            ];
            
            $monthNumber = $monthNames[strtolower($dataFormatada[1])];
            $targetDate = new DateTime($dataFormatada[0].'-'.$monthNumber.'-'.$dataFormatada[2]);
            $currentDate = new DateTime();
            $interval = $currentDate->diff($targetDate);
            $diasPassados = $interval->days;

            $rankings = $crawler->filter('tr th:contains("Ranking dos mais vendidos") + td')->text();
            $rankings = preg_replace('/\([^)]+\)/', '', $rankings);
            $rankings = preg_split('/\s*Nº\s*/', $rankings, -1, PREG_SPLIT_NO_EMPTY);

            $numberFormat = explode(' ', $reviewNumber);
            $numeroInteiro = (int)str_replace('.', '', $numberFormat[0]);
            $mediaVendasDia = floor($numeroInteiro * 10 / $diasPassados);
        }else{
            $disponivelDesde = "Não encontrado";
            $diasPassados = "Não encontrado";
            $rankings = "Não encontrado";
            $mediaVendasDia = "Não encontrado";
        }

        if($crawler->filter('td.a-text-right.a-nowrap a.a-size-base.a-link-normal')->eq(0)){
            $fiveStars = $crawler->filter('td.a-text-right.a-nowrap a.a-size-base.a-link-normal')->eq(0)->text()." de avaliações possuem 5 estrelas";
        }else{
            $fiveStars = "0% de avaliações possuem 5 estrelas";
        }

        if($crawler->filter('td.a-text-right.a-nowrap a.a-size-base.a-link-normal')->eq(1)->count() > 0){
            $fourStars = $crawler->filter('td.a-text-right.a-nowrap a.a-size-base.a-link-normal')->eq(1)->text()." de avaliações possuem 4 estrelas";
        }else{
            $fourStars = "0% de avaliações possuem 4 estrelas";
        }

        if($crawler->filter('td.a-text-right.a-nowrap a.a-size-base.a-link-normal')->eq(2)->count() > 0){
            $threeStars = $crawler->filter('td.a-text-right.a-nowrap a.a-size-base.a-link-normal')->eq(2)->text()." de avaliações possuem 3 estrelas";
        }else{
            $threeStars = "0% de avaliações possuem 3 estrelas";
        }

        if($crawler->filter('td.a-text-right.a-nowrap a.a-size-base.a-link-normal')->eq(3)->count() > 0){
            $twoStars = $crawler->filter('td.a-text-right.a-nowrap a.a-size-base.a-link-normal')->eq(3)->text()." de avaliações possuem 2 estrelas";
        }else{
            $twoStars = "0% de avaliações possuem 2 estrelas";
        }

        if($crawler->filter('td.a-text-right.a-nowrap a.a-size-base.a-link-normal')->eq(4)->count() > 0){
            $oneStars = $crawler->filter('td.a-text-right.a-nowrap a.a-size-base.a-link-normal')->eq(4)->text()." de avaliações possuem 1 estrelas";
        }else{
            $oneStars = "0% de avaliações possuem 1 estrelas";
        }

        
        //$fiveStars = $crawler->filter('td.a-text-right.a-nowrap span.a-size-base')->eq(0)->text()." de avaliações possuem 5 estrelas";
        /* $fourStars = $crawler->filter('td.a-text-right.a-nowrap a.a-size-base.a-link-normal')->eq(1)->text()." de avaliações possuem 4 estrelas";
        $threeStars = $crawler->filter('td.a-text-right.a-nowrap a.a-size-base.a-link-normal')->eq(2)->text()." de avaliações possuem 3 estrelas";
        $twoStars = $crawler->filter('td.a-text-right.a-nowrap a.a-size-base.a-link-normal')->eq(3)->text()." de avaliações possuem 2 estrelas";
        $oneStars = $crawler->filter('td.a-text-right.a-nowrap a.a-size-base.a-link-normal')->eq(4)->text()." de avaliações possuem 1 estrelas";
 */
        $stars = [
            $fiveStars,
            $fourStars,
            $threeStars,
            $twoStars,
            $oneStars
        ];

        if($crawler->filter('.a-section.olp-link-widget a.a-touch-link')->count() > 0){
            $offersNumber = $crawler->filter('.a-section.olp-link-widget a.a-touch-link')->text();
            $offersNumber = explode(' ofertas', $offersNumber);
            $offersNumber = explode(' ', $offersNumber[0]);
            $offersNumber = end($offersNumber). " vendedores";
        }else{
            $offersNumber = "1 vendedor";
        }
        

        return response()->json([
            'title' => $title,
            'price' => $price,
            'review' => $review,
            'reviewNumber' => $reviewNumber,
            'enviado' => $enviado,
            'vendido' => $vendido,
            'logistica' => $logistica,
            'minEstoque' => $quantidade,
            'marca' => $marca,
            'dataEntrega' => $dataEntrega,
            'ASIN' => $asin,

            'data' => $disponivelDesde,
            'diasOnline' => $diasPassados." dia(s)",
            'rankings' => $rankings,
            'mediaVendasDia' => $mediaVendasDia,

            'stars' => $stars,

            'offers' => $offersNumber

        ]);
    }
}




/*
Vendedor e suas informações;

reclamações boas e ruims (em porcentagem, por estrela e quais são);

Todos os detalhes técnicos

É possível criar historico de preço em produtos desejados através de monitoramento;

Para mais detalhes seria preciso utilizar API's amazon entre outros recursos.

*/