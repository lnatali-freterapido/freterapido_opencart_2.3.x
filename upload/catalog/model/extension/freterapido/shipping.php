<?php

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
                $volume['altura'] = (float) $this->default_dimensions['height'];
            }

            if (!$volume['largura']) {
                $volume['largura'] = (float) $this->default_dimensions['width'];
            }

            if (!$volume['comprimento']) {
                $volume['comprimento'] = (float) $this->default_dimensions['length'];
            }

            if (!$volume['peso']) {
                $volume['peso'] = (float) $this->default_dimensions['weight'] * $volume['quantidade'];
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
            $this->config['filtro'] = (int) $filter;
        }

        return $this;
    }

    /**
     * @param int $limit
     * @return $this
     */
    public function set_limit($limit) {
        if ($limit) {
            $this->config['limite'] = (int) $limit;
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
            throw new UnexpectedValueException(json_encode($response));
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
