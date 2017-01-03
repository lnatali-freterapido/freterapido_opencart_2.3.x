<?php
class ControllerShippingFreteRapido extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('shipping/freterapido');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('freterapido', $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            if(version_compare(VERSION, '2.2.0.0', '>')) {
                $this->response->redirect($this->url->link('extension/extension', 'token=' . $this->session->data['token'], 'SSL'));
            } else {
                $this->response->redirect($this->url->link('extension/shipping', 'token=' . $this->session->data['token'], 'SSL'));
            }
        }

        $data['heading_title'] = $this->language->get('heading_title');

        $data['text_edit'] = $this->language->get('text_edit');
        $data['text_enabled'] = $this->language->get('text_enabled');
        $data['text_disabled'] = $this->language->get('text_disabled');

        $data['text_correios_valor_declarado'] = $this->language->get('text_correios_valor_declarado');
        $data['text_correios_mao_propria'] = $this->language->get('text_correios_mao_propria');
        $data['text_correios_aviso_recebimento'] = $this->language->get('text_correios_aviso_recebimento');

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
        $data['entry_postcode']= $this->language->get('entry_postcode');
        $data['entry_cnpj']= $this->language->get('entry_cnpj');
        $data['entry_ie']= $this->language->get('entry_ie');
        $data['entry_results']= $this->language->get('entry_results');
        $data['entry_limit']= $this->language->get('entry_limit');
        $data['entry_post_deadline']= $this->language->get('entry_post_deadline');
        $data['entry_post_cost']= $this->language->get('entry_post_cost');

        $data['help_freterapido_token'] = $this->language->get('help_freterapido_token');
        $data['help_post_deadline'] = $this->language->get('help_post_deadline');
        $data['help_post_cost'] = $this->language->get('help_post_cost');

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

        if (isset($this->error['ie'])) {
            $data['error_ie'] = $this->error['ie'];
        } else {
            $data['error_ie'] = '';
        }

        if (isset($this->error['postcode'])) {
            $data['error_postcode'] = $this->error['postcode'];
        } else {
            $data['error_postcode'] = '';
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
            'href' => $this->url->link('extension/shipping', 'token=' . $this->session->data['token'], 'SSL')
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('shipping/freterapido', 'token=' . $this->session->data['token'], 'SSL')
        );

        $data['action'] = $this->url->link('shipping/freterapido', 'token=' . $this->session->data['token'], 'SSL');

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

        if (isset($this->request->post['freterapido_ie'])) {
            $data['freterapido_ie'] = $this->request->post['freterapido_ie'];
        } else {
            $data['freterapido_ie'] = $this->config->get('freterapido_ie');
        }

        if (isset($this->request->post['freterapido_correios_valor_declarado'])) {
            $data['freterapido_correios_valor_declarado'] = $this->request->post['freterapido_correios_valor_declarado'];
        } else {
            $data['freterapido_correios_valor_declarado'] = $this->config->get('freterapido_correios_valor_declarado');
        }

        if (isset($this->request->post['freterapido_correios_mao_propria'])) {
            $data['freterapido_correios_mao_propria'] = $this->request->post['freterapido_correios_mao_propria'];
        } else {
            $data['freterapido_correios_mao_propria'] = $this->config->get('freterapido_correios_mao_propria');
        }

        if (isset($this->request->post['freterapido_correios_aviso_recebimento'])) {
            $data['freterapido_correios_aviso_recebimento'] = $this->request->post['freterapido_correios_aviso_recebimento'];
        } else {
            $data['freterapido_correios_aviso_recebimento'] = $this->config->get('freterapido_correios_aviso_recebimento');
        }

        if (isset($this->request->post['freterapido_post_deadline'])) {
            $data['freterapido_post_deadline'] = $this->request->post['freterapido_post_deadline'];
        } else {
            $data['freterapido_post_deadline'] = $this->config->get('freterapido_post_deadline');
        }

        if (isset($this->request->post['freterapido_post_cost'])) {
            $data['freterapido_post_cost'] = $this->request->post['freterapido_post_cost'];
        } else {
            $data['freterapido_post_cost'] = $this->config->get('freterapido_post_cost');
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

        if (isset($this->request->post['freterapido_postcode'])) {
            $data['freterapido_postcode'] = $this->request->post['freterapido_postcode'];
        } else {
            $data['freterapido_postcode'] = $this->config->get('freterapido_postcode');
        }

        if (isset($this->request->post['freterapido_msg_prazo'])) {
            $data['freterapido_msg_prazo'] = $this->request->post['freterapido_msg_prazo'];
        } else {
            $data['freterapido_msg_prazo'] = $this->config->get('freterapido_msg_prazo');
        }

        if (isset($this->request->post['freterapido_token'])) {
            $data['freterapido_token'] = $this->request->post['freterapido_token'];
        } else {
            $data['freterapido_token'] = $this->config->get('freterapido_token');
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        if (version_compare(VERSION, '2.2') < 0) {
            $this->response->setOutput($this->load->view('shipping/freterapido.tpl', $data));
        } else {
            $this->response->setOutput($this->load->view('shipping/freterapido', $data));
        }
    }

    protected function validate() {
        if (!$this->user->hasPermission('modify', 'shipping/freterapido')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (!$this->request->post['freterapido_cnpj']) {
            $this->error['cnpj'] = $this->language->get('error_cnpj');
        }

        if (!$this->request->post['freterapido_ie']) {
            $this->error['ie'] = $this->language->get('error_ie');
        }

        if (!$this->request->post['freterapido_postcode']) {
            $this->error['postcode'] = $this->language->get('error_postcode');
        }

        if (!$this->request->post['freterapido_token']) {
            $this->error['token'] = $this->language->get('error_token');
        }

        return !$this->error;
    }
}
