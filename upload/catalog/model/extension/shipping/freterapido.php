<?php
class FreterapidoHttp {
    static function do_request($url, $params = array(), $method = 'POST') {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $data_string = json_encode($params);

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
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
}

class FreterapidoShipping {
    private $config;
    private $sender;
    private $receiver;
    private $dispatcher;
    private $volumes;

    private $default_dimensions = [
        'height' => 0.5,
        'width' => 0.5,
        'length' => 0.5,
        'weight' => 1
    ];

    public function __construct(array $config) {
        $this->config = array_merge([
            'tipo_cobranca' => 1,
            'tipo_frete' => 1,
            'ecommerce' => true,
        ], $config);
    }

    public function set_default_dimensions(array $dimensions) {
        foreach ($this->default_dimensions as $dimension => $value) {
            if (!isset($dimensions[$dimension])) {
                continue;
            }

            $new_value = (float) $dimensions[$dimension];

            if ($new_value < $value) {
                continue;
            }

            $this->default_dimensions[$dimension] = $new_value;
        }

        return $this;
    }

    public function add_sender(array $sender) {
        $this->sender = $sender;

        return $this;
    }

    public function add_receiver(array $receiver) {
        $this->receiver = $receiver;

        return $this;
    }

    public function add_dispatcher(array $dispatcher) {
        $this->dispatcher = $dispatcher;

        return $this;
    }

    public function add_volumes(array $volumes) {
        $this->volumes = array_map(function ($volume) {
            if (!$volume['altura']) {
                $volume['altura'] = $this->default_dimensions['height'];
            }

            if (!$volume['largura']) {
                $volume['largura'] = $this->default_dimensions['width'];
            }

            if (!$volume['comprimento']) {
                $volume['comprimento'] = $this->default_dimensions['length'];
            }

            if (!$volume['peso']) {
                $volume['peso'] = $this->default_dimensions['weight'] * $volume['quantidade'];
            }

            return $volume;
        }, $volumes);

        return $this;
    }

    /**
     * @param int $filter
     * @return $this
     */
    public function set_filter($filter) {
        if ($filter) {
            $this->config['filtro'] = $filter;
        }

        return $this;
    }

    /**
     * @param int $limit
     * @return $this
     */
    public function set_limit($limit) {
        if ($limit) {
            $this->config['limite'] = $limit;
        }

        return $this;
    }

    private function format_request() {
        $request = array();

        if ($this->dispatcher) {
            $request['expedidor'] = $this->dispatcher;
        }

        return array_merge(
            $request,
            array(
                'remetente' => $this->sender,
                'destinatario' => $this->receiver,
                'volumes' => $this->volumes,
                'tipo_cobranca' => 1,
                'tipo_frete' => 1,
                'ecommerce' => true,
            ),
            $this->config
        );
    }

    public function get_quote() {
        $response = FreterapidoHttp::do_request(FR_API_URL . 'embarcador/v1/quote-simulator', $this->format_request());

        if ((int)$response['info']['http_code'] === 401) {
            throw new InvalidArgumentException();
        }

        $result = $response['result'];

        if (!$result || !isset($result['transportadoras']) || count($result['transportadoras']) === 0) {
            throw new UnexpectedValueException();
        }

        $result['transportadoras'] = array_map(function ($carrier) {
            if (strtolower($carrier['nome']) === 'correios') {
                $carrier['nome'] .= " - {$carrier['servico']}";
            }

            return $carrier;
        }, $result['transportadoras']);

        return $result;
    }
}

class ModelExtensionShippingFreteRapido extends Model
{
    private $sender;
    private $receiver;
    private $volumes;

    private $manufacturing_deadline = 0;

    /**
     * Será usada pelo produto que não tenha uma categoria do FR definida para ele
     *
     * @var int
     */
    private $default_fr_category = 999;

    function getQuote($address) {
        define('FR_API_URL', 'http://api-externa.freterapido.app/');

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
            $shipping = new FreterapidoShipping([
                'token' => $this->config->get('freterapido_token'),
                'codigo_plataforma' => 'opencart2',
                'custo_adicional' => $this->config->get('freterapido_post_cost') ?: 0,
                'prazo_adicional' => $this->config->get('freterapido_post_deadline') ?: 0,
                'percentual_adicional' => $this->config->get('freterapido_additional_percentage') / 100,
            ]);

            $response = $shipping
                ->add_receiver($this->receiver)
                ->add_sender($this->sender)
                ->set_default_dimensions([
                    'length' => $this->config->get('freterapido_length'),
                    'width' => $this->config->get('freterapido_width'),
                    'height' => $this->config->get('freterapido_height'),
                ])
                ->add_volumes($this->volumes)
                ->set_filter($this->config->get('freterapido_results'))
                ->set_limit($this->config->get('freterapido_limit'))
                ->get_quote();
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
        $this->load->model('catalog/product');
        $this->load->model('catalog/category');
        $this->load->model('catalog/fr_category');

        $products = $this->cart->getProducts();

        $this->volumes = $this->getVolumes($products);
        $this->sender = $this->getSender();
        $this->receiver = $this->getReceiver($address);
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
     * Formata a oferta retornada pela API para o esperado pelo OpenCart
     *
     * @param $key
     * @param $carrier
     * @return array
     */
    function formatOffer($key, $carrier) {
        $price = $carrier['preco_frete'];

        $text_offer_part_one = $this->language->get('text_offer_part_one');
        $text_offer_part_two_singular = $this->language->get('text_offer_part_two_singular');
        $text_offer_part_two_plural = $this->language->get('text_offer_part_two_plural');

        // Soma o prazo de fabricação ao prazo de entrega da Transportadora
        $deadline = $carrier['prazo_entrega'] + $this->manufacturing_deadline;
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
            'cost' => $carrier['custo_frete'],
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
                'altura' => $height,
                'largura' => $width,
                'comprimento' => $length,
                'peso' => $weight,
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
