<?php
class ControllerExtensionShippingFreteRapido extends Controller {
    private $error = array();

    public function install() {
        $this->load->model('extension/event');
        $this->load->model('localisation/language');
        $this->load->model('localisation/order_status');
        $this->load->language('extension/shipping/freterapido');

        $this->model_extension_event->addEvent('freterapido_add_order_history', 'catalog/model/checkout/order/addOrderHistory/after', 'extension/shipping/freterapido/eventAddOrderHistory');
        $this->model_extension_event->addEvent('freterapido_add_order', 'catalog/controller/checkout/confirm/after', 'extension/shipping/freterapido/storeShipping');

        // Insere o status que será usado para a contratação
        $languages = $this->model_localisation_language->getLanguages();
        $new_order_status = array();
        $text_status_awaiting_shipment = $this->language->get('text_status_awaiting_shipment');

        foreach ($languages as $language) {
            $new_order_status['order_status'][$language['language_id']] = array('name' => $text_status_awaiting_shipment);
        }

        $this->model_localisation_order_status->addOrderStatus($new_order_status);
    }

    public function index() {
        $this->load->language('extension/shipping/freterapido');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('freterapido', $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('extension/extension', 'token=' . $this->session->data['token'], 'SSL'));
        }

        $data['heading_title'] = $this->language->get('heading_title');

        $data['text_edit'] = $this->language->get('text_edit');
        $data['text_enabled'] = $this->language->get('text_enabled');
        $data['text_disabled'] = $this->language->get('text_disabled');

        $data['text_results_nofilter'] = $this->language->get('text_results_nofilter');
        $data['text_results_cheaper'] = $this->language->get('text_results_cheaper');
        $data['text_results_faster'] = $this->language->get('text_results_faster');

        $data['text_none'] = $this->language->get('text_none');
        $data['text_yes'] = $this->language->get('text_yes');
        $data['text_no'] = $this->language->get('text_no');
        $data['text_select_all'] = $this->language->get('text_select_all');
        $data['text_unselect_all'] = $this->language->get('text_unselect_all');

        $data['entry_freterapido_token'] = $this->language->get('entry_freterapido_token');
        $data['entry_freterapido_token_code'] = $this->language->get('entry_freterapido_token_code');
        $data['entry_cost'] = $this->language->get('entry_cost');
        $data['entry_status'] = $this->language->get('entry_status');
        $data['entry_sort_order'] = $this->language->get('entry_sort_order');
        $data['entry_cnpj']= $this->language->get('entry_cnpj');
        $data['entry_results']= $this->language->get('entry_results');
        $data['entry_limit']= $this->language->get('entry_limit');
        $data['entry_dimension']= $this->language->get('entry_dimension');
        $data['entry_length']= $this->language->get('entry_length');
        $data['entry_width']= $this->language->get('entry_width');
        $data['entry_height']= $this->language->get('entry_height');

        $data['help_cnpj'] = $this->language->get('help_cnpj');
        $data['help_freterapido_token'] = $this->language->get('help_freterapido_token');
        $data['help_dimension'] = $this->language->get('help_dimension');
        $data['help_dimension_unit'] = $this->language->get('help_dimension_unit');

        $data['button_save'] = $this->language->get('button_save');
        $data['button_cancel'] = $this->language->get('button_cancel');

        $data['tab_general'] = $this->language->get('tab_general');

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['cnpj'])) {
            $data['error_cnpj'] = $this->error['cnpj'];
        } else {
            $data['error_cnpj'] = '';
        }

        if (isset($this->error['token'])) {
            $data['error_token'] = $this->error['token'];
        } else {
            $data['error_token'] = '';
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'token=' . $this->session->data['token'], 'SSL')
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_shipping'),
            'href' => $this->url->link('extension/extension', 'token=' . $this->session->data['token'] . '&type=shipping', 'SSL')
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/shipping/freterapido', 'token=' . $this->session->data['token'], 'SSL')
        );

        $data['action'] = $this->url->link('extension/shipping/freterapido', 'token=' . $this->session->data['token'], 'SSL');

        $data['cancel'] = $this->url->link('extension/extension', 'token=' . $this->session->data['token'] . '&type=shipping', true);

        if (isset($this->request->post['freterapido_status'])) {
            $data['freterapido_status'] = $this->request->post['freterapido_status'];
        } else {
            $data['freterapido_status'] = $this->config->get('freterapido_status');
        }

        if (isset($this->request->post['freterapido_cnpj'])) {
            $data['freterapido_cnpj'] = $this->request->post['freterapido_cnpj'];
        } else {
            $data['freterapido_cnpj'] = $this->config->get('freterapido_cnpj');
        }

        if (isset($this->request->post['freterapido_results'])) {
            $data['freterapido_results'] = $this->request->post['freterapido_results'];
        } else {
            $data['freterapido_results'] = $this->config->get('freterapido_results');
        }

        if (isset($this->request->post['freterapido_limit'])) {
            $data['freterapido_limit'] = $this->request->post['freterapido_limit'];
        } else {
            $data['freterapido_limit'] = $this->config->get('freterapido_limit');
        }

        if (isset($this->request->post['freterapido_msg_prazo'])) {
            $data['freterapido_msg_prazo'] = $this->request->post['freterapido_msg_prazo'];
        } else {
            $data['freterapido_msg_prazo'] = $this->config->get('freterapido_msg_prazo');
        }

        if (isset($this->request->post['freterapido_length'])) {
            $data['freterapido_length'] = $this->request->post['freterapido_length'];
        } else {
            $data['freterapido_length'] = $this->config->get('freterapido_length');
        }

        if (isset($this->request->post['freterapido_width'])) {
            $data['freterapido_width'] = $this->request->post['freterapido_width'];
        } else {
            $data['freterapido_width'] = $this->config->get('freterapido_width');
        }

        if (isset($this->request->post['freterapido_height'])) {
            $data['freterapido_height'] = $this->request->post['freterapido_height'];
        } else {
            $data['freterapido_height'] = $this->config->get('freterapido_height');
        }

        if (isset($this->request->post['freterapido_token'])) {
            $data['freterapido_token'] = $this->request->post['freterapido_token'];
        } else {
            $data['freterapido_token'] = $this->config->get('freterapido_token');
        }

        if (isset($this->request->post['freterapido_sort_order'])) {
            $data['freterapido_sort_order'] = $this->request->post['freterapido_sort_order'];
        } else {
            $data['freterapido_sort_order'] = $this->config->get('freterapido_sort_order');
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/shipping/freterapido', $data));
    }

    protected function validate() {
        if (!$this->user->hasPermission('modify', 'extension/shipping/freterapido')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (!$this->request->post['freterapido_cnpj']) {
            $this->error['cnpj'] = $this->language->get('error_cnpj');
        }

        if (!$this->request->post['freterapido_token']) {
            $this->error['token'] = $this->language->get('error_token');
        }

        return !$this->error;
    }

    public function uninstall() {
        $this->load->model('extension/event');
        $this->load->model('localisation/order_status');
        $this->load->language('extension/shipping/freterapido');

        $this->model_extension_event->deleteEvent('freterapido_add_order_history');
        $this->model_extension_event->deleteEvent('freterapido_add_order');

        // Exclui o status usado na contratação
        $statuses = $this->model_localisation_order_status->getOrderStatuses();
        $text_status_awaiting_shipment = $this->language->get('text_status_awaiting_shipment');

        $fr_order_status = array_filter($statuses, function ($status) use ($text_status_awaiting_shipment) {
            return $status['name'] == $text_status_awaiting_shipment;
        });

        if (count($fr_order_status) > 0) {
            $this->model_localisation_order_status->deleteOrderStatus(array_pop($fr_order_status)['order_status_id']);
        }
    }
}
