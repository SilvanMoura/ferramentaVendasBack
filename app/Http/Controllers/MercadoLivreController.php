<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;

class MercadoLivreController extends Controller
{
    public function welcome($itemId)
    {
        return "Bem-Vindo";
    }
    public function getProductDetails($itemId)
    {
        $clientId = '4229798206724254'; // Substitua pelo seu client_id do Mercado Livre
        $clientSecret = 'DGqAylwZZN0LzAdkmyiUrEXFKJ1CcC6A'; // Substitua pelo seu client_secret do Mercado Livre

        // Crie um cliente Guzzle
        $client = new Client();

        // Faça a solicitação para a API de Produtos
        $accessToken = $this->getAccessToken($clientId, $clientSecret);

        // Faça a solicitação para a API de Produtos com o token de acesso
        $response = $client->get("https://api.mercadolibre.com/items/$itemId");
        
        if ($response->getStatusCode() === 200) {
            $productData = json_decode($response->getBody());
            return $productData;
        } else if ($response->getStatusCode() === 404) {
            // Item não encontrado, trate o erro adequadamente
            return response()->json(['error' => 'Item não encontrado na API do Mercado Livre'], 404);
        } else {
            // Trate outros erros de acordo com o status da resposta
            return response()->json(['error' => 'Erro na solicitação à API do Mercado Livre'], 400);
        }
        
    }

    private function getAccessToken($clientId, $clientSecret) {
        $client = new Client();
    
        $response = $client->post("https://api.mercadolibre.com/oauth/token", [
            'form_params' => [
                'grant_type' => 'client_credentials',
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
            ],
        ]);
    
        if ($response->getStatusCode() === 200) {
            $data = json_decode($response->getBody(), true);
            return $data['access_token'];
        } else {
            // Trate o erro de obtenção de token aqui, por exemplo, lançando uma exceção
            throw new \Exception('Erro ao obter o token de acesso');
        }
    }
    
}
