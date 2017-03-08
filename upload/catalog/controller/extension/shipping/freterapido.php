<?php
class ControllerExtensionShippingFreterapido extends Controller {
    public function eventAddOrderHistory($route, $args) {
        foreach (['config', 'http', 'hire_shipping', 'helpers'] as $file_name) {
            include_once(DIR_APPLICATION . 'model/extension/freterapido/' . $file_name . '.php');
        }

        $this->load->model('account/order');
        $this->load->model('checkout/order');
        $this->load->model('checkout/order_meta');
        $this->load->model('setting/setting');
        $this->load->model('account/custom_field');
        $this->load->language('extension/shipping/freterapido');

        $order_id = $args[0];
        $cnpj = fix_zip_code($this->config->get('freterapido_cnpj'));
        $token = $this->config->get('freterapido_token');
        $order_histories = $this->model_account_order->getOrderHistories($order_id);
        $last_history = array_pop($order_histories);

        if ($last_history['status'] != $this->language->get('text_status_awaiting_shipment')) {
            return;
        }

        $quote = $this->model_checkout_order_meta->getMeta($order_id, 'freterapido_quotes');

        // Verifica se é uma oferta própria
        if ($quote === false) {
            return;
        }

        $order = $this->model_checkout_order->getOrder($order_id);

        $custom_fields = $order['custom_field'];

        if (!is_array($custom_fields)) {
            return;
        }

        $custom_field_key = array_reduce(array_keys($custom_fields), function ($carry, $key) {
            $custom_field = $this->model_account_custom_field->getCustomField($key);

            if ($custom_field && $custom_field['location'] == 'account' && stripos($custom_field['name'], 'CPF') !== false) {
                return $key;
            }

            return 0;
        });

        // Verifica se tem CPF (campo customizado) nos dados do cliente
        if ($custom_field_key === 0) {
            return;
        }

        $cpf = fix_zip_code($custom_fields[$custom_field_key]);

        try {
            $hire_shipping = new FreterapidoHireShipping($token);
            $response = $hire_shipping
                ->add_sender(array('cnpj' => $cnpj))
                ->add_receiver(array(
                    'cnpj_cpf' => $cpf,
                    'nome' => "{$order['firstname']} {$order['lastname']}",
                    'email' => $order['email'],
                    'telefone' => $order['telephone'],
                    'endereco' => array(
                        'cep' => fix_zip_code($order['shipping_postcode']),
                        'rua' => $order['shipping_address_1'],
                        'bairro' => $order['shipping_address_2'],
                        'numero' => '',
                    )
                ))
                ->hire_quote($quote['token'], $quote['oferta']);

            $this->model_checkout_order_meta->addMeta($order_id, 'freterapido_shippings', array_values($response));
        } catch (UnexpectedValueException $e) {
            $this->log->write($e->getMessage());
        } catch (Exception $e) {}
    }

    public function storeShipping() {
        $this->load->model('checkout/order_meta');

        $order_id = $this->session->data['order_id'];
        $shipping_method = $this->session->data['shipping_method'];

        if ($shipping_method && isset($shipping_method['meta_data'])) {
            $this->model_checkout_order_meta->addMeta($order_id, 'freterapido_quotes', $shipping_method['meta_data']);
        }
    }
}
