<?php
class ModelExtensionShippingFreteRapido extends Model
{
    private $api_url = 'http://api-externa.freterapido.app/embarcador/v1/quote-simulator';

    private $sender;
    private $receiver;
    private $volumes;

    private $manufacturing_deadline = 0;

    /**
     * Dimensões padrão em KG
     *
     * @var array
     */
    private $default_dimensions = [
        'height' => 0.5,
        'width' => 0.5,
        'length' => 0.5,
        'weight' => 1
    ];

    /**
     * Será usada pelo produto que não tenha uma categoria do FR definida para ele
     *
     * @var int
     */
    private $default_fr_category = 999;

    function getQuote($address) {
        $this->load->language('extension/shipping/freterapido');

        $method_data = array();

        if (!$this->validate($address)) {
            return $method_data;
        }

        $this->setup($address);

        $method_data = array(
            'code' => 'freterapido',
            'title' => $this->language->get('text_title'),
            'quote' => array(),
            'sort_order' => $this->config->get('freterapido_sort_order'),
            'error' => false
        );

        try {
            $response = $this->callApi();
        } catch (InvalidArgumentException $invalid_argument) {
            // Quando for erro na autenticação, mostra no "cart"
            $method_data['error'] = $this->language->get('text_error_auth_api');
            return $method_data;
        } catch (UnexpectedValueException $unexpected_value) {
            // Outros erros não faz nada
            return array();
        }

        $quote_data = array();

        // Prepara o retorno das ofertas
        foreach ($response['transportadoras'] as $key => $carrier) {
            $quote_data[] = $this->formatOffer($key, $carrier);
        }

        return array_merge($method_data, ['quote' => $quote_data]);
    }

    /**
     * Seta os valores da requisição
     *
     * @param $address
     */
    function setup($address) {
        $this->configureDimensions();

        $this->load->model('catalog/product');
        $this->load->model('catalog/category');
        $this->load->model('catalog/fr_category');

        $products = $this->cart->getProducts();

        $this->volumes = $this->getVolumes($products);
        $this->sender = $this->getSender();
        $this->receiver = $this->getReceiver($address);
    }

    /**
     * Define quais serão as dimensões padrão, a definida pela loja ou padrão ($default_dimensions)
     */
    function configureDimensions() {
        foreach ($this->default_dimensions as $dimension => $value) {
            $new_value = (float) $this->config->get("freterapido_{$dimension}");

            if ($new_value < $value) {
                continue;
            }

            $this->default_dimensions[$dimension] = $new_value;
        }
    }

    /**
     * Valida o endereço do destinatário
     *
     * @param $address
     * @return bool
     */
    function validate($address) {
        if (!isset($address['postcode']) || strlen($this->onlyNumbers($address['postcode'])) !== 8) {
            return false;
        }

        return true;
    }

    /**
     * Faz a chamada na API de simulação
     *
     * @return mixed
     * @throws HttpInvalidParamException
     * @throws HttpResponseException
     */
    function callApi() {
        $request = $this->prepareRequest();

        // Faz a requisição na API
        $response = $this->doRequest($this->api_url, $request);

        if ((int)$response['info']['http_code'] === 401) {
            throw new InvalidArgumentException();
        }

        $result = $response['result'];

        if (!$result || !isset($result['transportadoras']) || count($result['transportadoras']) === 0) {
            throw new UnexpectedValueException();
        }

        return $result;
    }

    /**
     * Formata os dados a serem enviados
     *
     * @return array
     */
    function prepareRequest() {
        $request = array(
            'remetente' => $this->sender,
            'destinatario' => $this->receiver,
            'volumes' => $this->volumes,

            'tipo_cobranca' => 1,
            'tipo_frete' => 1,
            'ecommerce' => true,

            'token' => $this->config->get('freterapido_token')
        );

        // Adiciona o filtro se tiver
        if ($filter = $this->config->get('freterapido_results')) {
            $request['filtro'] = $filter;
        }

        // Define o limite de resultados configurado
        if ($limit = $this->config->get('freterapido_limit')) {
            $request['limite'] = $limit;
        }

        return $request;
    }

    /**
     * Formata a oferta retornada pela API para o esperado pelo OpenCart
     *
     * @param $key
     * @param $carrier
     * @return array
     */
    function formatOffer($key, $carrier) {
        $posting_cost = $this->config->get('freterapido_post_cost') ?: 0;
        $price = $carrier['preco'] + $posting_cost;

        $text_offer_part_one = $this->language->get('text_offer_part_one');
        $text_offer_part_two_singular = $this->language->get('text_offer_part_two_singular');
        $text_offer_part_two_plural = $this->language->get('text_offer_part_two_plural');

        // Soma o prazo de postagem e fabricação ao prazo de entrega da Transportadora
        $deadline_for_posting = $this->config->get('freterapido_post_deadline') ?: 0;
        $deadline = $carrier['prazo_entrega'] + $deadline_for_posting + $this->manufacturing_deadline;
        $deadline_text = $deadline == 1 ? $text_offer_part_two_singular : $text_offer_part_two_plural;

        // Coloca o símbolo da moeda do usuário, mas não converte o valor
        $price_formatted = $this->currency->format($price, $this->session->data['currency'], 1);

        $text = "$text_offer_part_one $deadline $deadline_text - $price_formatted";

        $title = $carrier['nome'];

        if (strtolower($title) === 'correios') {
            $title .= " - {$carrier['servico']}";
        }

        return array(
            'code' => 'freterapido.' . $key,
            'title' => $title,
            'cost' => $carrier['preco'],
            'tax_class_id' => 0,
            'text' => $text
        );
    }

    /**
     * Cria e preenche os volumes com os dados necessários
     *
     * @param $products
     * @return array
     */
    function getVolumes($products) {
        return array_map(function ($product) {
            // Converte as medidas para o esperado pela API
            $length_class_id = $product['length_class_id'];
            $weight_class_id = $product['weight_class_id'];

            $product_from_db = $this->model_catalog_product->getProduct($product['product_id']);

            $height = $this->convertDimensionToMeters($length_class_id, $product['height']);
            $width = $this->convertDimensionToMeters($length_class_id, $product['width']);
            $length = $this->convertDimensionToMeters($length_class_id, $product['length']);
            $weight = $this->convertWeightToKG($weight_class_id, $product['weight']);

            $volume = array(
                'quantidade' => $product['quantity'],
                'altura' => $height ?: $this->default_dimensions['height'],
                'largura' => $width ?: $this->default_dimensions['width'],
                'comprimento' => $length ?: $this->default_dimensions['length'],
                'peso' => $weight ?: ($this->default_dimensions['weight'] * $product['quantity']),
                'valor' => $product['total'],
                'sku' => $product_from_db['sku']
            );

            $findFRCategory = function ($category) {
                return $this->findCategory($category['category_id']);
            };

            $notNull = function ($category) {
                return $category !== null;
            };

            $categories = $this->model_catalog_product->getCategories($product['product_id']);
            $fr_categories = array_filter(array_map($findFRCategory, $categories), $notNull);

            $fr_category = ['code' => $this->default_fr_category];

            // Pega a primeira categoria do Frete Rápido encontrada se tiver
            if ($category = array_shift($fr_categories)) {
                $fr_category = $this->model_catalog_fr_category->getCategory($category['category_id']);
            }

            // O prazo de fabricação a ser somado no prazo de entrega é o do produto com maior prazo
            if ($product_from_db['manufacturing_deadline'] > $this->manufacturing_deadline) {
                $this->manufacturing_deadline = $product_from_db['manufacturing_deadline'];
            }

            return array_merge($volume, ['tipo' => $fr_category['code']]);
        }, $products);
    }

    /**
     * Procura a categoria mais próxima do produto que esteja relacionada com alguma do FR
     *
     * @param $category_id
     * @return null|array
     */
    function findCategory($category_id) {
        $category = $this->model_catalog_category->getCategory($category_id);

        if ($fr_category = $this->model_catalog_fr_category->getCategory($category_id)) {
            return $category;
        }

        // Não relacionou nenhuma das categorias vinculadas ao produto com uma categoria do Frete Rápido
        if ($category['parent_id'] == 0) {
            return null;
        }

        return $this->findCategory($category['parent_id']);
    }

    function getSender() {
        return array(
            'cnpj' => $this->onlyNumbers($this->config->get('freterapido_cnpj'))
        );
    }

    function getReceiver($address) {
        return array(
            'tipo_pessoa' => 1,
            'endereco' => array(
                'cep' => $this->onlyNumbers($address['postcode'])
            )
        );
    }

    /**
     * Realiza a requisição na no endereço da API
     *
     * @param $url
     * @param array $params
     * @return array
     */
    function doRequest($url, $params = array()) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $data_string = json_encode($params);

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data_string)
        ));

        $result = curl_exec($ch);
        $info = curl_getinfo($ch);

        curl_close($ch);

        return ['info' => $info, 'result' => json_decode($result, true)];
    }

    /**
     * Retorna apenas os números do $value passado
     *
     * @param $value
     * @return mixed
     */
    function onlyNumbers($value) {
        return preg_replace("/[^0-9]/", '', $value);
    }

    private function convertDimensionToMeters($length_class_id, $dimension) {
        if (!is_numeric($dimension)) {
            return $dimension;
        }

        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "length_class lc LEFT JOIN " . DB_PREFIX . "length_class_description lcd ON (lc.length_class_id = lcd.length_class_id) WHERE lc.length_class_id = '" . (int)$length_class_id . "' AND lcd.language_id = '" . (int)$this->config->get('config_language_id') . "'");
        $length_class = $query->row;

        if (isset($length_class['unit']) && $length_class['unit'] == 'mm') {
            $dimension /= 10;
        }

        return $dimension / 100;
    }

    private function convertWeightToKG($weight_class_id, $weight) {
        if (!is_numeric($weight)) {
            return $weight;
        }

        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "weight_class wc LEFT JOIN " . DB_PREFIX . "weight_class_description wcd ON (wc.weight_class_id = wcd.weight_class_id) WHERE wc.weight_class_id = '" . (int)$weight_class_id . "' AND wcd.language_id = '" . (int)$this->config->get('config_language_id') . "'");
        $weight_class = $query->row;

        if (isset($weight_class['unit']) && $weight_class['unit'] == 'g') {
            $weight /= 1000;
        }

        return $weight;
    }
}
