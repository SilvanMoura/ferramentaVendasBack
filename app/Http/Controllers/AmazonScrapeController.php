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

        
        if($crawler->filter('tr.a-histogram-row.a-align-center td.a-text-right.a-nowrap.a-nowrap a.a-size-base.a-link-normal')->eq(0)->count() > 0){
            $fiveStars = $crawler->filter('tr.a-histogram-row.a-align-center td.a-text-right.a-nowrap.a-nowrap a.a-size-base.a-link-normal')->eq(0)->text();
        }else{
            $fiveStars = "0%";
        }

        if($crawler->filter('tr.a-histogram-row.a-align-center td.a-text-right.a-nowrap.a-nowrap a.a-size-base.a-link-normal')->eq(1)->count() > 0){
            $fourStars = $crawler->filter('tr.a-histogram-row.a-align-center td.a-text-right.a-nowrap.a-nowrap a.a-size-base.a-link-normal')->eq(1)->text();
        }else{
            $fourStars = "0%";
        }

        if($crawler->filter('tr.a-histogram-row.a-align-center td.a-text-right.a-nowrap.a-nowrap a.a-size-base.a-link-normal')->eq(2)->count() > 0){
            $threeStars = $crawler->filter('tr.a-histogram-row.a-align-center td.a-text-right.a-nowrap.a-nowrap a.a-size-base.a-link-normal')->eq(2)->text();
        }else{
            $threeStars = "0%";
        }

        if($crawler->filter('tr.a-histogram-row.a-align-center td.a-text-right.a-nowrap.a-nowrap a.a-size-base.a-link-normal')->eq(3)->count() > 0){
            $twoStars = $crawler->filter('tr.a-histogram-row.a-align-center td.a-text-right.a-nowrap.a-nowrap a.a-size-base.a-link-normal')->eq(3)->text();
        }else{
            $twoStars = "0%";
        }

        if($crawler->filter('tr.a-histogram-row.a-align-center td.a-text-right.a-nowrap.a-nowrap a.a-size-base.a-link-normal')->eq(4)->count() > 0){
            $oneStars = $crawler->filter('tr.a-histogram-row.a-align-center td.a-text-right.a-nowrap.a-nowrap a.a-size-base.a-link-normal')->eq(4)->text();
        }else{
            $oneStars = "0%";
        }
        //$fiveStars = $crawler->filter('tr.a-histogram-row.a-align-center td.a-text-right.a-nowrap.a-nowrap a.a-size-base.a-link-normal')->eq(3)->text();
        //$fiveStars = $crawler->filter('td.a-text-right.a-nowrap span.a-size-base')->eq(0)->text()." de avaliações possuem 5 estrelas";
        /* $fourStars = $crawler->filter('td.a-text-right.a-nowrap a.a-size-base.a-link-normal')->eq(1)->text()." de avaliações possuem 4 estrelas";
        $threeStars = $crawler->filter('td.a-text-right.a-nowrap a.a-size-base.a-link-normal')->eq(2)->text()." de avaliações possuem 3 estrelas";
        $twoStars = $crawler->filter('td.a-text-right.a-nowrap a.a-size-base.a-link-normal')->eq(3)->text()." de avaliações possuem 2 estrelas";
        $oneStars = $crawler->filter('td.a-text-right.a-nowrap a.a-size-base.a-link-normal')->eq(4)->text()." de avaliações possuem 1 estrelas";
        */
        $stars = [
            $fiveStars." de avaliações possuem 5 estrelas",
            $fourStars." de avaliações possuem 4 estrelas",
            $threeStars." de avaliações possuem 3 estrelas",
            $twoStars." de avaliações possuem 2 estrelas",
            $oneStars." de avaliações possuem 1 estrela"
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

    public function calculatorMargin($asin)
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
        $price = str_replace(".", "", $price[1]);
        $price = str_replace(",", ".", $price);

        // Converte o número formatado em um float
        $price = (float)$price;

        if ($crawler->filter("#cm-cr-dp-review-header")->text()) {
            $review = $crawler->filter("#cm-cr-dp-review-header")->text();
            $reviewNumber = "Nenhuma avaliação disponível";
        } else {
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

        //$titulo = $crawler->filter('.a-size-medium.a-spacing-small.secHeader h1')->text();
        $disponivelDesde = $crawler->filter('tr th:contains("Disponível para compra desde") + td');
        if($disponivelDesde->count() > 0){

            $rankings = $crawler->filter('tr th:contains("Ranking dos mais vendidos") + td')->text();
            $rankings = preg_replace('/\([^)]+\)/', '', $rankings);
            $rankings = preg_replace('/\b\d+\b|\bem\b/', '', $rankings);
            $rankings = preg_split('/\s*Nº\s*/', $rankings, -1, PREG_SPLIT_NO_EMPTY); 
            $rankings = preg_replace('/,  /', '', $rankings); 
        }else{
            $rankings = "Não encontrado";
        }

        $comissoes = [
            "Cozinha" => 13,
            "Eletrônicos" => 13,
            "TV e Cinema em Casa" => 10,
            "Casa" => 14,
            "Alimentos e Bebidas" => 9,
            "Bebidas Alcoólicas" => 9,
            "Bolsas, Malas e Mochilas" => 15,
            "Ferramentas e Construção" => 14,
            "Ferramentas e Materiais de Construção" => 14,
            "Bebês" => 12,
            "Beleza de Luxo" => 14,
            "Produtos Industriais e Científicos" => 13,
            "Pet Shop" => 12,
            "Automotivo" => 12,
            "Dvd e Blu-ray" => 15,
            "Games e Consoles" => 13,
            "Livros" => 15,
            "Papelaria e Escritório" => 14,
            "Roupas, Calçados E Acessórios" => 15,
            "Beleza" => 13,
            "Brinquedos e Jogos" => 12,
            "Eletrodomésticos" => 8,
            "Celulares e Comunicação" => 13,
            "Esportes, Aventura e Lazer" => 13,
            "Computadores e Informática" => 12,
            "Câmeras e Foto" => 13,
            "Móveis" => [
                "até R$ 200,00" => 15,
                "acima de R$ 200,00" => 9
            ],
            "Instrumentos Musicais" => 15,
            "Saúde e Bem-Estar" => 11,
            "Jardim e Piscina" => 12,
            "Cuidados Pessoais" => 10,
            "Cd e Vinil" => 15,
            "Limpeza de Casa" => 15,
            "Acessórios" => 15,
            "Material Escolar" => 15,
            "Loja Kindle" => 15
        ];

        $tarifaFBA = [
            100 => 12.95,
            200 => 13.45,
            300 => 13.95,
            400 => 14.45,
            500 => 14.95,
            750 => 15.45,
            1000 => 15.95,
            1500 => 16.95,
            2000 => 17.95,
            3000 => 18.95,
            4000 => 19.95,
            5000 => 20.95,
            6000 => 25.95,
            7000 => 27.95,
            8000 => 29.95,
            9000 => 31.95,
            10000 => 43.45
        ];

        $tarifasDbaFixed = [
            "all" => 5.50,
        ];

        $tarifasDbaVariable = [
            250 => 22.70,
            500 => 23.70,
            1000 => 25.45,
            2000 => 28.45,
            3000 => 33.48,
            4000 => 34.95,
            5000 => 37.45,
            6000 => 45.20,
            7000 => 46.95,
            8000 => 48.20,
            9000 => 53.95,
            10000 => 70.45
        ];

        if(array_key_exists($rankings[0], $comissoes)){
            $comissaoProduct = $rankings[0];
            $comissaoPercent = $comissoes[$rankings[0]];
        }else{
            $comissaoProduct = "Outros";
            $comissaoPercent = 15;
        }
        
        $rowElement = $crawler->filter('#productDetails_techSpec_section_1 th:contains("Peso do produto")');
        if($rowElement->count() > 0){
            $rowElement = $rowElement->first()->nextAll()->filter('td')->text();
        }

        $rowElementAlt = $crawler->filter('#productDetails_techSpec_section_1 th:contains("Dimensões do produto")');
        if($rowElementAlt->count() > 0){
            $rowElementAlt = $rowElementAlt->first()->nextAll()->filter('td')->text();
            $rowElementAlt = explode('; ', $rowElementAlt);

            if (strpos($rowElementAlt[1], "Quilogramas") !== false) {
                $rowElementAlt = str_replace(" Quilogramas", '', $rowElementAlt[1]);
                $rowElement = floatval(str_replace(',', '.', $rowElementAlt))*1000;
            }

            if (strpos($rowElementAlt[1], "g") !== false) {
                $rowElementAlt = str_replace(" g", '', $rowElementAlt[1]);
                $rowElement = floatval(str_replace(',', '.', $rowElementAlt));
            }

        }
        

        if (strpos($rowElement, "Kilograms") !== false) {
            $peso = preg_replace("/[^0-9,]/", "", $rowElement);
            $peso = str_replace(",", ".", $peso);
            $peso = floatval($peso*1000);
        }else{
            $peso = $rowElement;
        }

        if($logistica == "FBA"){
            foreach ($tarifaFBA as $limitePeso => $tarifa) {
                if ($peso <= $limitePeso) {
                    $valorTarifa = $tarifa;
                    break;
                }else{
                    $valorTarifa = 43.45+(3.05*(($peso - 10000)/1000));
                }
            }
        }else{
            if($price > 79){
                foreach ($tarifasDbaVariable as $limitePeso => $tarifa) {
                    if ($peso < $limitePeso) {
                        $valorTarifa = $tarifa;
                        break;
                    }else{
                        $valorTarifa = 70.45+(3.50*(($peso - 10000)/1000));
                    }
                }
            }else{
                $valorTarifa = $tarifasDbaFixed["all"];
            }
        }



        

        return response()->json([
            'title' => $title,
            'price' => $price,
            'reviewNumber' => $reviewNumber,
            'enviado' => $enviado,
            'vendido' => $vendido,
            'logistica' => $logistica,
            'ASIN' => $asin,

            'comissaoProduct' => $comissaoProduct,
            'comissaoPercent' => $comissaoPercent,
            'peso' => $peso,
            'valorTarifa' => $valorTarifa
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